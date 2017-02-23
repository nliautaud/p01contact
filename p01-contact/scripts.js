var CaptchaCallback = function() {
    var captchas = document.querySelectorAll(".recaptcha");
    for(var i=0; i < captchas.length; i++) {
        grecaptcha.render(captchas[i].id, {"sitekey" : "'.$key.'"});
    }
};
