document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll('.sidebar .nav-link').forEach(function (element) {

    element.addEventListener('click', function (e) {

      // alert();
      var curruntLi = $(this).closest('.nav-item').index();
      var parentDiv = $(this).closest('.nav-item');
      let nextEl = element.nextElementSibling;
      let parentEl = element.parentElement;
      if(parentDiv.hasClass('has-submenu'))
      {
        $(".has-submenu").each(function () {
          if ($(this).index() == curruntLi) {
            if ($(this).hasClass('open') ) {
              $(this).removeClass("open");
            } else {
              $(this).addClass("open");
            }
          }
          else {
            $(this).removeClass("open");
          }
          console.log(curruntLi + "--" + $(this).index());
        });

        $("body").removeClass("shrink");
      }
      

      if (nextEl) {
        e.preventDefault();
        let mycollapse = new bootstrap.Collapse(nextEl);

        if (nextEl.classList.contains('show')) {
          mycollapse.hide();
        } else {
          mycollapse.show();
          // find other submenus with class=show
          var opened_submenu = parentEl.parentElement.querySelector('.submenu.show');
          // if it exists, then close all of them
          if (opened_submenu) {
            new bootstrap.Collapse(opened_submenu);
          }
        }
      }
    }); // addEventListener
  }) // forEach
});
// DOMContentLoaded  end