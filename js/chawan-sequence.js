/**
 * Zazen Matcha & Tea Co. — Cinematic Scroll-Controlled Whisking Sequence (#vessel-study)
 *
 * Implements a high-performance, canvas-rendered image sequence scrubber
 * depicting a traditional Japanese matcha-whisking ritual in a handcrafted ceramic chawan.
 *
 * Features:
 *   - Canvas-based frame renderer with Retina/High-DPI support
 *   - Progressive preloading: Frame 1 renders instantly, remaining frames load sequentially
 *   - Bidirectional scroll scrub: Scrolling down advances whisking, scrolling up rewinds
 *   - Desktop pointer hover scrubbing without scroll-jacking
 *   - Exponential easing loop (requestAnimationFrame) for smooth frame transitions
 *   - IntersectionObserver lifecycle management: halts loop when off-screen
 *   - Graceful fallback: If single frame or asset load failure occurs, holds clean presentation
 *   - Accessibility: Respects prefers-reduced-motion media query
 */

(function () {
    'use strict';

    function initChawanSequence() {
        var section   = document.getElementById('vessel-study');
        var track     = document.getElementById('chawan-scroll-track');
        var stage     = document.getElementById('chawan-stage');
        var photo     = document.getElementById('chawan-photo');
        var canvas    = document.getElementById('chawan-sequence-canvas');

        if (!section || !track || !stage || !canvas) {
            return;
        }

        var ctx = canvas.getContext('2d');
        if (!ctx) {
            return; // Canvas unsupported, fallback <img> remains visible
        }

        var progressBar = section.querySelector('.chawan-film-progress-bar');
        var stepEls     = section.querySelectorAll('[data-ritual-step]');

        // 1. Parse frame list from data-frames attribute
        var rawFrames = stage.getAttribute('data-frames');
        var frameUrls = [];
        try {
            frameUrls = JSON.parse(rawFrames) || [];
        } catch (e) {
            frameUrls = [];
        }

        if (!frameUrls.length && photo) {
            frameUrls = [photo.getAttribute('src')];
        }

        var totalFrames = frameUrls.length;
        var hasSequence = totalFrames > 1;

        // 2. Accessibility: prefers-reduced-motion check
        var reduceMotion = false;
        try {
            var mediaQuery = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
            if (mediaQuery) {
                reduceMotion = mediaQuery.matches;
                if (mediaQuery.addEventListener) {
                    mediaQuery.addEventListener('change', function (e) {
                        reduceMotion = e.matches;
                        if (reduceMotion) {
                            section.classList.remove('sequence-scrubbable');
                            renderFrame(0);
                        } else if (hasSequence) {
                            section.classList.add('sequence-scrubbable');
                        }
                    });
                }
            }
        } catch (e) {
            reduceMotion = false;
        }

        // Cache and state
        var loadedImages   = new Array(totalFrames);
        var loadFailed     = new Array(totalFrames);
        var currentFrame   = -1;
        var scrollProgress = 0;
        var hoverProgress  = -1;
        var shownProgress  = 0;
        var isHovering     = false;
        var isVisible      = true;
        var rafId          = null;

        function clamp(v, lo, hi) {
            return v < lo ? lo : (v > hi ? hi : v);
        }

        // 3. Canvas sizing with HiDPI support
        function resizeCanvas() {
            var rect = canvas.getBoundingClientRect();
            var dpr = Math.min(window.devicePixelRatio || 1, 2);
            var displayWidth = Math.max(1, Math.round((rect.width || 580) * dpr));
            var displayHeight = Math.max(1, Math.round((rect.height || 435) * dpr));

            if (canvas.width !== displayWidth || canvas.height !== displayHeight) {
                canvas.width = displayWidth;
                canvas.height = displayHeight;
                if (currentFrame >= 0) {
                    drawLoadedImage(currentFrame);
                }
            }
        }

        function drawLoadedImage(idx) {
            // Find closest loaded image if requested frame is still buffering
            var imgToDraw = loadedImages[idx];
            if (!imgToDraw) {
                for (var i = idx; i >= 0; i--) {
                    if (loadedImages[i]) {
                        imgToDraw = loadedImages[i];
                        break;
                    }
                }
            }
            if (!imgToDraw) {
                for (var j = idx + 1; j < totalFrames; j++) {
                    if (loadedImages[j]) {
                        imgToDraw = loadedImages[j];
                        break;
                    }
                }
            }
            if (!imgToDraw) return;

            var cw = canvas.width;
            var ch = canvas.height;
            var iw = imgToDraw.naturalWidth || imgToDraw.width;
            var ih = imgToDraw.naturalHeight || imgToDraw.height;

            if (iw === 0 || ih === 0) return;

            ctx.clearRect(0, 0, cw, ch);
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';

            // Cover fit (exact match with 4:3 figure, zero cropping)
            var scale = Math.max(cw / iw, ch / ih);
            var nw = iw * scale;
            var nh = ih * scale;
            var dx = (cw - nw) * 0.5;
            var dy = (ch - nh) * 0.5;

            ctx.drawImage(imgToDraw, dx, dy, nw, nh);
        }

        function renderFrame(index) {
            var safeIndex = clamp(index, 0, totalFrames - 1);
            if (safeIndex === currentFrame) return;
            currentFrame = safeIndex;
            drawLoadedImage(safeIndex);
        }

        // 4. Update UI Chrome (Progress Bar & Ritual Steps)
        function updateChrome(p) {
            if (progressBar) {
                progressBar.style.transform = 'scaleX(' + p.toFixed(4) + ')';
            }
            if (stepEls.length) {
                var stepIndex = 0;
                if (p > 0.75) {
                    stepIndex = 3;
                } else if (p > 0.45) {
                    stepIndex = 2;
                } else if (p > 0.18) {
                    stepIndex = 1;
                }
                for (var s = 0; s < stepEls.length; s++) {
                    stepEls[s].classList.toggle('is-active', s === stepIndex);
                }
            }
        }

        // 5. Scroll progress calculation through sticky track
        function readScrollProgress() {
            var rect  = track.getBoundingClientRect();
            var vh    = window.innerHeight || document.documentElement.clientHeight;
            var range = rect.height - vh;
            if (range <= 0) {
                range = rect.height + vh;
                return clamp((vh - rect.top) / range, 0, 1);
            }
            return clamp(-rect.top / range, 0, 1);
        }

        // 6. Smooth render loop
        function tick() {
            rafId = null;
            if (!isVisible) return;

            scrollProgress = readScrollProgress();
            var target = (isHovering && hoverProgress >= 0) ? hoverProgress : scrollProgress;

            // Exponential easing for buttery-smooth ritual progression
            shownProgress += (target - shownProgress) * 0.18;
            if (Math.abs(target - shownProgress) < 0.001) {
                shownProgress = target;
            }

            var safeP = clamp(shownProgress, 0, 1);
            var targetIndex = Math.min(totalFrames - 1, Math.round(safeP * (totalFrames - 1)));
            renderFrame(targetIndex);
            updateChrome(safeP);

            if (Math.abs(target - shownProgress) > 0.001) {
                schedule();
            }
        }

        function schedule() {
            if (!rafId && isVisible) {
                rafId = requestAnimationFrame(tick);
            }
        }

        // 7. Progressive frame loader
        function preloadAllFrames() {
            var queueIndex = 1;

            function loadNext() {
                if (queueIndex >= totalFrames) return;
                var idx = queueIndex++;
                var img = new Image();
                img.onload = function () {
                    loadedImages[idx] = img;
                    loadNext();
                };
                img.onerror = function () {
                    loadFailed[idx] = true;
                    loadNext();
                };
                img.src = frameUrls[idx];
            }

            // Start preloading remaining frames after frame 0 is ready
            loadNext();
        }

        // 8. Load initial frame immediately
        var initialImg = new Image();
        initialImg.onload = function () {
            loadedImages[0] = initialImg;
            resizeCanvas();
            renderFrame(0);
            section.classList.add('sequence-ready');

            if (hasSequence && !reduceMotion) {
                section.classList.add('sequence-scrubbable');
                preloadAllFrames();
                schedule();
            } else {
                section.classList.add('sequence-static');
            }
        };
        initialImg.onerror = function () {
            // Keep fallback <img> visible
            section.classList.add('sequence-static');
        };
        initialImg.src = frameUrls[0];

        // 9. Event Listeners
        function onScroll() {
            if (hasSequence && !reduceMotion) {
                schedule();
            }
        }

        function onResize() {
            resizeCanvas();
            if (hasSequence && !reduceMotion) {
                schedule();
            }
        }

        // Desktop hover scrubbing across stage
        function onPointerMove(e) {
            if (!hasSequence || reduceMotion) return;
            var rect = stage.getBoundingClientRect();
            if (rect.width > 0) {
                var relX = (e.clientX - rect.left) / rect.width;
                hoverProgress = clamp(relX, 0, 1);
                isHovering = true;
                schedule();
            }
        }

        function onPointerLeave() {
            isHovering = false;
            hoverProgress = -1;
            schedule();
        }

        stage.addEventListener('pointermove', onPointerMove, { passive: true });
        stage.addEventListener('pointerleave', onPointerLeave, { passive: true });
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onResize, { passive: true });

        // 10. IntersectionObserver for energy efficiency
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                var entry = entries[0];
                isVisible = entry ? entry.isIntersecting : true;
                if (isVisible) {
                    schedule();
                } else if (rafId) {
                    cancelAnimationFrame(rafId);
                    rafId = null;
                }
            }, { threshold: 0.05 });
            observer.observe(section);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChawanSequence);
    } else {
        initChawanSequence();
    }
})();
