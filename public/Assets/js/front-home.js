// Animated counters
  document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.stat-number[data-count]');
    
    const animateCounter = (el) => {
      const target = parseInt(el.dataset.count);
      const duration = 2000;
      const start = performance.now();
      
      const update = (currentTime) => {
        const elapsed = currentTime - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.floor(eased * target);
        
        if (progress < 1) {
          requestAnimationFrame(update);
        } else {
          el.textContent = target;
        }
      };
      
      requestAnimationFrame(update);
    };
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
    
    // Gallery filtering
    const filterBtns = document.querySelectorAll('.filter-btn');
    const artworkCards = document.querySelectorAll('.artwork-card');
    
    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const filter = btn.dataset.filter;
        
        artworkCards.forEach(card => {
          if (filter === 'all' || card.dataset.category === filter) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
    
    // Favorite toggle
    document.querySelectorAll('.artwork-favorite').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        btn.classList.toggle('favorited');
        const svg = btn.querySelector('svg');
        if (btn.classList.contains('favorited')) {
          svg.setAttribute('fill', 'currentColor');
          btn.style.background = 'var(--color-terracotta)';
          btn.style.color = 'white';
        } else {
          svg.setAttribute('fill', 'none');
          btn.style.background = 'white';
          btn.style.color = 'inherit';
        }
      });
    });
  });
