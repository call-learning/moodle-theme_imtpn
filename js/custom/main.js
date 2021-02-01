$(document).ready(function() {
  $('#language-trigger-selection').click(function() {
    $('#language-list').toggleClass('-active');
  })

  $('#mask-language-choice').click(function() {
    $('#language-list').removeClass('-active');
  })

  $('.language-list .language-choice').click(function() {
    $('#language-list').removeClass('-active');
  })
})