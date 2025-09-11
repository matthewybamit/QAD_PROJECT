   document.addEventListener('DOMContentLoaded', function () {
        // Smooth hover on inputs
        document.querySelectorAll('input, textarea, select').forEach(el => {
            el.classList.add('transition-all', 'duration-200');
        });

        // Phone formatting
        const phone = document.querySelector('#contact_phone');
        if (phone) {
            phone.addEventListener('input', function () {
                let v = this.value.replace(/\D/g, '');
                if (v.length >= 11) {
                    v = v.replace(/(\d{4})(\d{3})(\d{4})/, '($1)-$2-$3');
                    this.value = v;
                }
            });
        }
    });


    function scrollToCenter(id) {
  const section = document.getElementById(id);
  if (section) {
    section.scrollIntoView({
      behavior: 'smooth',
      block: 'center', // centers vertically
      inline: 'nearest'
    });
  }
}