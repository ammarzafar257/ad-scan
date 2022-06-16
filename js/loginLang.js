let currentLang = 'en' // default
try {
    currentLang = window.navigator.userLanguage || window.navigator.language;
    currentLang = currentLang.split('-')[0]  // removes the country code
} catch (err) {
    console.error('problem determining language, defualting to user prefrence');
    currentLang = 'en';
}
// change the lang
changeLang(currentLang);
$(document).ready(function () {
    document.documentElement.setAttribute('lang', currentLang);
});