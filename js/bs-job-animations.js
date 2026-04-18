(function() {
    'use strict';

    function setupCanvas(hero) {
        var canvas = hero.querySelector('.bs-hero-canvas');
        if (!canvas) return null;
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;

        function resize() {
            var rect = hero.getBoundingClientRect();
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            canvas.style.width = rect.width + 'px';
            canvas.style.height = rect.height + 'px';
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            return rect;
        }

        var rect = resize();
        var mouse = { x: -1000, y: -1000 };

        hero.addEventListener('mousemove', function(e) {
            var r = hero.getBoundingClientRect();
            mouse.x = e.clientX - r.left;
            mouse.y = e.clientY - r.top;
        });
        hero.addEventListener('mouseleave', function() {
            mouse.x = -1000;
            mouse.y = -1000;
        });

        window.addEventListener('resize', function() { rect = resize(); });

        return {
            canvas: canvas, ctx: ctx, mouse: mouse,
            getW: function() { return hero.getBoundingClientRect().width; },
            getH: function() { return hero.getBoundingClientRect().height; }
        };
    }

    /* ===== LIGHT TRAILS (Web Developer - Red) ===== */
    document.querySelectorAll('.bs-hero-lighttrails').forEach(function(hero) {
        var s = setupCanvas(hero);
        if (!s) return;

        var particles = [];
        var trailLength = 12;

        for (var i = 0; i < 30; i++) {
            var trail = [];
            var sx = Math.random() * 1400;
            var sy = Math.random() * 500;
            for (var t = 0; t < trailLength; t++) trail.push({ x: sx, y: sy });
            particles.push({
                trail: trail,
                speed: 1.5 + Math.random() * 2.5,
                offset: Math.random() * Math.PI * 2,
                hue: Math.random()
            });
        }

        var time = 0;
        var animId = null;

        function draw() {
            var w = s.getW(), h = s.getH();
            s.ctx.clearRect(0, 0, w, h);
            time += 0.01;

            var tx = s.mouse.x > 0 ? s.mouse.x : w / 2 + Math.sin(time) * 100;
            var ty = s.mouse.y > 0 ? s.mouse.y : h / 2 + Math.cos(time * 0.7) * 50;

            particles.forEach(function(p) {
                var head = p.trail[0];
                var dx = tx - head.x;
                var dy = ty - head.y;
                var dist = Math.sqrt(dx * dx + dy * dy);

                if (dist > 5) {
                    head.x += (dx / dist) * p.speed + Math.sin(time * 3 + p.offset) * 1.2;
                    head.y += (dy / dist) * p.speed + Math.cos(time * 2.5 + p.offset) * 1.2;
                } else {
                    head.x += Math.sin(time * 4 + p.offset) * 2;
                    head.y += Math.cos(time * 3 + p.offset) * 2;
                }

                for (var i = 1; i < p.trail.length; i++) {
                    p.trail[i].x += (p.trail[i - 1].x - p.trail[i].x) * 0.35;
                    p.trail[i].y += (p.trail[i - 1].y - p.trail[i].y) * 0.35;
                }

                var r = Math.round(227 + p.hue * 0);
                var g = Math.round(11 + p.hue * 30);
                var b = Math.round(92 + p.hue * 30);

                for (var i = p.trail.length - 1; i >= 0; i--) {
                    var alpha = ((p.trail.length - i) / p.trail.length) * 0.25;
                    var size = ((p.trail.length - i) / p.trail.length) * 4;
                    s.ctx.beginPath();
                    s.ctx.arc(p.trail[i].x, p.trail[i].y, size, 0, Math.PI * 2);
                    s.ctx.fillStyle = 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
                    s.ctx.fill();
                }
            });

            animId = requestAnimationFrame(draw);
        }

        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) { if (!animId) draw(); }
            else { if (animId) { cancelAnimationFrame(animId); animId = null; } }
        }, { threshold: 0.1 });
        observer.observe(hero);
    });

    /* ===== ORBIT RINGS (Web Designer - Blue) ===== */
    document.querySelectorAll('.bs-hero-orbitrings').forEach(function(hero) {
        var s = setupCanvas(hero);
        if (!s) return;

        var rings = [];
        var ringCount = 6;

        for (var i = 0; i < ringCount; i++) {
            var dotCount = 5 + i * 3;
            var dots = [];
            for (var d = 0; d < dotCount; d++) {
                dots.push({
                    angle: (Math.PI * 2 / dotCount) * d,
                    speed: (0.003 + Math.random() * 0.005) * (i % 2 === 0 ? 1 : -1),
                    size: 2 + Math.random() * 2
                });
            }
            rings.push({ radius: 50 + i * 40, dots: dots, alpha: 0.05 + (ringCount - i) * 0.015 });
        }

        var animId = null;

        function draw() {
            var w = s.getW(), h = s.getH();
            s.ctx.clearRect(0, 0, w, h);

            var cx = w / 2, cy = h / 2;
            if (s.mouse.x > 0) {
                cx += (s.mouse.x - w / 2) * 0.15;
                cy += (s.mouse.y - h / 2) * 0.15;
            }

            rings.forEach(function(ring) {
                s.ctx.beginPath();
                s.ctx.arc(cx, cy, ring.radius, 0, Math.PI * 2);
                s.ctx.strokeStyle = 'rgba(29,71,161,' + ring.alpha + ')';
                s.ctx.lineWidth = 1;
                s.ctx.stroke();

                ring.dots.forEach(function(dot) {
                    dot.angle += dot.speed;
                    var x = cx + Math.cos(dot.angle) * ring.radius;
                    var y = cy + Math.sin(dot.angle) * ring.radius;

                    s.ctx.beginPath();
                    s.ctx.arc(x, y, dot.size, 0, Math.PI * 2);
                    s.ctx.fillStyle = 'rgba(29,71,161,' + (ring.alpha + 0.12) + ')';
                    s.ctx.fill();

                    s.ctx.beginPath();
                    s.ctx.moveTo(cx, cy);
                    s.ctx.lineTo(x, y);
                    s.ctx.strokeStyle = 'rgba(29,71,161,0.02)';
                    s.ctx.lineWidth = 0.5;
                    s.ctx.stroke();
                });
            });

            animId = requestAnimationFrame(draw);
        }

        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) { if (!animId) draw(); }
            else { if (animId) { cancelAnimationFrame(animId); animId = null; } }
        }, { threshold: 0.1 });
        observer.observe(hero);
    });

    /* ===== FLOATING SHAPES (Social Media - Green) ===== */
    document.querySelectorAll('.bs-hero-floatshapes').forEach(function(hero) {
        var s = setupCanvas(hero);
        if (!s) return;

        var shapes = [];
        for (var i = 0; i < 25; i++) {
            shapes.push({
                x: Math.random() * 1400,
                y: Math.random() * 500,
                size: 12 + Math.random() * 35,
                type: Math.floor(Math.random() * 3),
                rotation: Math.random() * Math.PI * 2,
                rotSpeed: (Math.random() - 0.5) * 0.01,
                vx: (Math.random() - 0.5) * 0.3,
                vy: (Math.random() - 0.5) * 0.3,
                alpha: 0.04 + Math.random() * 0.08,
                hue: Math.random()
            });
        }

        var animId = null;

        function draw() {
            var w = s.getW(), h = s.getH();
            s.ctx.clearRect(0, 0, w, h);

            shapes.forEach(function(sh) {
                var dx = sh.x - s.mouse.x;
                var dy = sh.y - s.mouse.y;
                var dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 150 && dist > 0) {
                    var force = (150 - dist) / 150 * 0.8;
                    sh.vx += (dx / dist) * force;
                    sh.vy += (dy / dist) * force;
                }

                sh.vx *= 0.98;
                sh.vy *= 0.98;
                sh.x += sh.vx;
                sh.y += sh.vy;
                sh.rotation += sh.rotSpeed;

                if (sh.x < -50) sh.x = w + 50;
                if (sh.x > w + 50) sh.x = -50;
                if (sh.y < -50) sh.y = h + 50;
                if (sh.y > h + 50) sh.y = -50;

                var r = Math.round(76 + sh.hue * 20);
                var g = Math.round(175 + sh.hue * 10);
                var b = Math.round(80 + sh.hue * 30);

                s.ctx.save();
                s.ctx.translate(sh.x, sh.y);
                s.ctx.rotate(sh.rotation);
                s.ctx.fillStyle = 'rgba(' + r + ',' + g + ',' + b + ',' + sh.alpha + ')';
                s.ctx.strokeStyle = 'rgba(' + r + ',' + g + ',' + b + ',' + (sh.alpha + 0.04) + ')';
                s.ctx.lineWidth = 1.5;

                if (sh.type === 0) {
                    s.ctx.beginPath();
                    s.ctx.arc(0, 0, sh.size / 2, 0, Math.PI * 2);
                    s.ctx.fill(); s.ctx.stroke();
                } else if (sh.type === 1) {
                    var half = sh.size / 2;
                    s.ctx.beginPath();
                    s.ctx.moveTo(-half, -half);
                    s.ctx.lineTo(half, -half);
                    s.ctx.lineTo(half, half);
                    s.ctx.lineTo(-half, half);
                    s.ctx.closePath();
                    s.ctx.fill(); s.ctx.stroke();
                } else {
                    s.ctx.beginPath();
                    s.ctx.moveTo(0, -sh.size / 2);
                    s.ctx.lineTo(sh.size / 2, sh.size / 2);
                    s.ctx.lineTo(-sh.size / 2, sh.size / 2);
                    s.ctx.closePath();
                    s.ctx.fill(); s.ctx.stroke();
                }
                s.ctx.restore();
            });

            animId = requestAnimationFrame(draw);
        }

        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) { if (!animId) draw(); }
            else { if (animId) { cancelAnimationFrame(animId); animId = null; } }
        }, { threshold: 0.1 });
        observer.observe(hero);
    });

    /* ===== WAVE RIPPLE (Business Analyst - Yellow) ===== */
    document.querySelectorAll('.bs-hero-waveripple').forEach(function(hero) {
        var s = setupCanvas(hero);
        if (!s) return;

        var time = 0;
        var animId = null;

        function draw() {
            var w = s.getW(), h = s.getH();
            s.ctx.clearRect(0, 0, w, h);
            time += 0.02;

            var mx = s.mouse.x > 0 ? s.mouse.x : w / 2;
            var my = s.mouse.y > 0 ? s.mouse.y : h / 2;

            var lineCount = 12;
            for (var l = 0; l < lineCount; l++) {
                s.ctx.beginPath();
                var baseY = (h / (lineCount + 1)) * (l + 1);
                var alpha = 0.06 + (l % 3) * 0.02;

                for (var x = 0; x <= w; x += 3) {
                    var dx = x - mx;
                    var dy = baseY - my;
                    var dist = Math.sqrt(dx * dx + dy * dy);

                    var wave1 = Math.sin(x * 0.008 + time + l * 0.5) * 12;
                    var wave2 = Math.sin(x * 0.015 - time * 0.7 + l * 0.3) * 6;
                    var cursorWave = 0;
                    if (dist < 300) {
                        cursorWave = Math.sin(dist * 0.03 - time * 3) * (1 - dist / 300) * 20;
                    }

                    var y = baseY + wave1 + wave2 + cursorWave;
                    if (x === 0) s.ctx.moveTo(x, y);
                    else s.ctx.lineTo(x, y);
                }

                s.ctx.strokeStyle = 'rgba(249,181,0,' + alpha + ')';
                s.ctx.lineWidth = 1.5;
                s.ctx.stroke();
            }

            animId = requestAnimationFrame(draw);
        }

        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) { if (!animId) draw(); }
            else { if (animId) { cancelAnimationFrame(animId); animId = null; } }
        }, { threshold: 0.1 });
        observer.observe(hero);
    });

    /* ===== TERMINAL TYPING (Web Developer) ===== */
    document.querySelectorAll('.bs-terminal-body').forEach(function(body) {
        var lines = body.querySelectorAll('.bs-terminal-line');
        var cursor = document.createElement('span');
        cursor.className = 'bs-cursor';
        cursor.textContent = '▋';
        cursor.style.cssText = 'color:#E30B5C;animation:bsBlink .7s step-end infinite;margin-left:2px;';

        var lineIndex = 0;
        var charIndex = 0;

        // Hide all lines and store their text
        var lineTexts = [];
        lines.forEach(function(line) {
            var textEl = line.querySelector('.bs-typed-text');
            if (textEl) {
                lineTexts.push(textEl.textContent);
                textEl.textContent = '';
            } else {
                lineTexts.push('');
            }
            line.style.display = 'none';
        });

        function typeLine() {
            if (lineIndex >= lines.length) {
                // Done, leave cursor blinking on last line
                return;
            }

            var line = lines[lineIndex];
            var textEl = line.querySelector('.bs-typed-text');
            var fullText = lineTexts[lineIndex];
            line.style.display = 'block';

            if (textEl) {
                textEl.textContent = '';
                textEl.appendChild(cursor);
            }

            function typeChar() {
                if (charIndex < fullText.length) {
                    if (textEl) {
                        textEl.textContent = fullText.substring(0, charIndex + 1);
                        textEl.appendChild(cursor);
                    }
                    charIndex++;
                    var speed = 25 + Math.random() * 35;
                    setTimeout(typeChar, speed);
                } else {
                    // Line done, move to next
                    charIndex = 0;
                    lineIndex++;
                    setTimeout(typeLine, 200);
                }
            }

            typeChar();
        }

        // Start typing after a short delay
        setTimeout(typeLine, 600);
    });

    /* ===== SCROLL REVEAL ANIMATIONS (All pages) ===== */
    var revealEls = document.querySelectorAll('.bs-job-section, .bs-job-two-col, .bs-ideal-card, .bs-job-about, .bs-job-footer-note');
    revealEls.forEach(function(el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });

    var revealObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(function(el) {
        revealObserver.observe(el);
    });

    /* Stagger children in two-col layouts */
    document.querySelectorAll('.bs-job-two-col').forEach(function(col) {
        var children = col.children;
        for (var i = 0; i < children.length; i++) {
            children[i].style.transitionDelay = (i * 0.15) + 's';
        }
    });

})();