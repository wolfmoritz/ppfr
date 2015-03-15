// Mark off ingredients
$('.step-ingredient-check').on('click', function() {
  if ($(this).is(':checked')) {
    $(this).parent().addClass('step-ingredient-strikethrough');
  } else {
    $(this).parent().removeClass('step-ingredient-strikethrough');
  }
});

// Fix step navigation
// var $stepNav = $('.step-nav');

// $(window).on('scroll', function() {

//   console.log($(window).scrollTop())

//   if($(window).scrollTop() >= 520) {
//     $stepNav.addClass('step-nav-fixed');
//     if($('.step-nav').is(':visible')) {
//       $('.step-row').css('margin-top','86px');
//     }
//   } else {
//     $stepNav.removeClass('step-nav-fixed');
//     $('.step-row').css('margin-top','0');
//   }
// });

(function($) {
  var priorStepId = '1';
  $.easing.easeOutQuad = function (x, t, b, c, d) {
        return -c *(t/=d)*(t-2) + b;
  };

  $('.step-nav-buttons > a').on('click', function(e) {
    e.preventDefault();
    var anchor = $(this).prop('href');
    var target = anchor.split('#')[1];
    var step = $('#'+target);
    var stepLeftPos = '+=' + (step.offset().left - 15);

    // Scroll down to row, then to step
    $('html,body').animate({scrollTop: step.offset().top - 150}, 'slow', 'easeOutQuad');
    $('.step-row').animate({scrollLeft: stepLeftPos}, 'slow', 'easeOutQuad');
  });

  var getBoundary = function(stepContainer, stepId) {
    if (stepContainer.getBoundingClientRect().left >= 0 && stepContainer.getBoundingClientRect().left < stepContainer.getBoundingClientRect().width/2 && stepId !== priorStepId) {
      activeStepNavButton(stepId);
      priorStepId = stepId;   
    }
  };

  var activeStepNavButton = function(currentStep) {
    var $navButtons = $('.step-nav-buttons');
    $('a.btn-primary', $navButtons).toggleClass('btn-default btn-primary');
    $navButtons.find('a[href="#step-'+currentStep+'"]').toggleClass('btn-default btn-primary');
  }

  var steps = $('.step-container');
  steps.each(function(i) {
    var el = this;
    var id = $(this).data('step')
    $('.step-row').on('scroll', function() { getBoundary(el, id); });
  });
})(jQuery);





// Set jumbotron background color
var defaults      = {
  selector: '.featured-image',
  parent: '.jumbotron-recipe',
  exclude: ['rgb(0,0,0)', 'rgba(255,255,255)'],
  normalizeTextColor:   false,
  normalizedTextColors:  {
    light:      "#fff",
    dark:       "#000"
  },
  lumaClasses:  {
    light:      "ab-light",
    dark:       "ab-dark"
  }
};
// $.adaptiveBackground.run(defaults)
