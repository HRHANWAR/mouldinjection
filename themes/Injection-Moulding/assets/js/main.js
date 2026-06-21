/**
 * Injection Moulding Theme - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
  // =============================================
    // 1. MOBILE MENU TOGGLE
    // =============================================
  //  navbar

   
// Mobile menu + services dropdown are handled by the inline script in header.php
// (it drives style.display directly). Duplicating handlers here caused conflicting
// toggle states and a null-reference crash on pages without these elements.



   
    // =============================================
    // 3. SCROLL REVEAL ANIMATION
    // =============================================
    const revealItems = document.querySelectorAll('.reveal');

    const revealObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12 }
    );

    revealItems.forEach(item => revealObserver.observe(item));


    // =============================================
    // 4. FAQ ACCORDION
    // =============================================
   const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const button = item.querySelector('button');
        const answer = item.querySelector('.answer');
        const badge = item.querySelector('.num-badge');
        const iconImg = item.querySelector('.faq-icon-img'); // JS yahan se image ko pakad raha hai

        button.addEventListener('click', () => {
            const isCurrentlyOpen = !answer.classList.contains('hidden');

            // Sabhi items ko band karne ka loop
            faqItems.forEach(otherItem => {
                otherItem.querySelector('.answer').classList.add('hidden');
                otherItem.querySelector('.num-badge').classList.replace('text-[#E9FFB2]', 'text-white/60');
                
                const otherIcon = otherItem.querySelector('.faq-icon-img');
                if (otherIcon) {
                    // BAND STATE: Arrow ko wapas 0 degree (niche) par le aata hai
                    otherIcon.style.transform = 'rotate(0deg)'; 
                }
            });

            // Agar click kiya gaya item band tha, to usay kholne ka logic
            if (!isCurrentlyOpen) {
                answer.classList.remove('hidden');
                badge.classList.replace('text-white/60', 'text-[#E9FFB2]');
                
                if (iconImg) {
                    // OPEN STATE: Arrow ko -180 degree ghumata hai taaki wo upar point kare
                    iconImg.style.transform = 'rotate(-90deg)'; 
                }
            }
        });
    });


    // =============================================
    // 5. ANIMATED COUNTERS
    // =============================================
    function animateCounter(el) {
        const target = parseFloat(el.getAttribute('data-target') || el.innerText.replace(/[^0-9.]/g, ''));
        const suffix = el.getAttribute('data-suffix') || '';
        const duration = 1500; // ms
        const steps = 60;
        const increment = target / steps;
        let current = 0;
        let step = 0;

        const timer = setInterval(() => {
            step++;
            current = Math.min(current + increment, target);
            el.textContent = Math.ceil(current).toLocaleString() + suffix;
            if (step >= steps) {
                el.textContent = target.toLocaleString() + suffix;
                clearInterval(timer);
            }
        }, duration / steps);
    }

    const counterObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.counter').forEach(el => counterObserver.observe(el));


    // =============================================
    // 6. SCROLL-ACTIVATED TIMELINE
    // =============================================
    const lineFiller = document.getElementById('line-filler');
    const timelineItems = document.querySelectorAll('.timeline-item');
    const dots = document.querySelectorAll('.dot');
    const innerDots = document.querySelectorAll('.inner-dot');

    if (lineFiller) {
        window.addEventListener('scroll', () => {
            const scrollPos = window.scrollY + (window.innerHeight * 0.6);
            const section = lineFiller.closest('section');
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;

            let progress = ((scrollPos - sectionTop) / sectionHeight) * 100;
            lineFiller.style.height = Math.min(Math.max(progress, 0), 100) + '%';

            timelineItems.forEach((item, index) => {
                const itemTop = item.getBoundingClientRect().top + window.scrollY;
                if (scrollPos > itemTop) {
                    if (dots[index]) {
                        dots[index].classList.add('active');
                    }
                    if (innerDots[index]) {
                        innerDots[index].style.background = '#E9FFB2';
                    }
                }
            });
        });
    }


    // =============================================
    // 7. STICKY HEADER SHADOW ON SCROLL
    // =============================================
    const siteHeader = document.getElementById('site-header');
    if (siteHeader) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                siteHeader.style.boxShadow = '0 4px 32px rgba(205,247,182,0.5)';
            } else {
                siteHeader.style.boxShadow = '0 2px 16px rgba(205,247,182,0.3)';
            }
        });
    }

 
   
});


