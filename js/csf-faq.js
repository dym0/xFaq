jQuery(document).ready(function($) {
  $('.faq-question').click(function() {
    var $btn = $(this);
    var $answer = $btn.next('.faq-answer');
    var $toggle = $btn.find('.faq-toggle');
    var isOpen = $btn.attr('aria-expanded') === 'true';

    $btn.attr('aria-expanded', !isOpen);
    $answer.slideToggle(200);
    $toggle.text(isOpen ? '+' : '–');
  });
});