/**
 * Front-end validation of sign-up form
 * @returns {boolean} boolean for if valid
 */
function validateSignup() {
    // reset the inputs
    resetInputs();

    // get all the sign up field values
    const firstName = $("#signup-first").val();
    const lastName = $("#signup-last").val();
    const email = $("#signup-email").val();
    const password = $("#signup-password").val();
    const confirmPassword = $("#signup-confirm-password").val();
    // const phone = $("#signup-phone").val();
    const companyName = $("#signup-company").val();
    // const companyAddress = $("#signup-address").val();
    // const postal = $("#signup-postal").val()
    // const city = $("#signup-city").val();
    // const province = $("#signup-province").val();
    // const country = $("#signup-country").val();
    const lang = $("#signup-lang").val();

    // verify required fields have a value, in bottom top top order for scrolling to top
    if (companyName === "") {
        document.getElementById("company-valid").style.display = "inline";
        document.getElementById("company-valid").innerText = "Required Field";
        document.getElementById("signup-company").classList.add("is-invalid");
        document.querySelector("#signup-company").classList.remove("is-valid")
        document.querySelector("#company-label").scrollIntoView();
    }
    if (confirmPassword ===  "") {
        document.getElementById("conPassword-valid").style.display = "inline";
        document.getElementById("conPassword-valid").innerText = "Required Field";
        document.getElementById("signup-confirm-password").classList.add("is-invalid");
        document.querySelector("#signup-confirm-password").classList.remove("is-valid")
        document.querySelector("#conPassword-label").scrollIntoView();
    }
    if (password ===  "") {
        document.getElementById("password-valid").style.display = "inline";
        document.getElementById("password-valid").innerText = "Required Field";
        document.getElementById("signup-password").classList.add("is-invalid");
        document.querySelector("#signup-password").classList.remove("is-valid")
        document.querySelector("#password-label").scrollIntoView();
    }
    if (email ===  "") {
        document.getElementById("email-valid").style.display = "inline";
        document.getElementById("email-valid").innerText = "Required Field";
        document.getElementById("signup-email").classList.add("is-invalid");
        document.querySelector("#signup-email").classList.remove("is-valid")
        document.querySelector("#email-label").scrollIntoView();
    }
    if (lastName ===  "") {
        document.getElementById("last-valid").style.display = "inline";
        document.getElementById("last-valid").innerText = "Required Field";
        document.getElementById("signup-last").classList.add("is-invalid");
        document.querySelector("#signup-last").classList.remove("is-valid")
        document.querySelector("#last-label").scrollIntoView();
    }
    if (firstName ===  "") {
        document.getElementById("first-valid").style.display = "inline";
        document.getElementById("first-valid").innerText = "Required Field";
        document.getElementById("signup-first").classList.add("is-invalid");
        document.querySelector("#signup-first").classList.remove("is-valid")
        document.querySelector("#first-label").scrollIntoView();
    }
    if (firstName ===  "" || lastName === "" || email === "" || password === "" || confirmPassword === "" || lang === "") {
        return false;
    }

    // verify email is a proper email address
    const emailRegex = /^([A-Za-z0-9_\-.])+@([A-Za-z0-9_\-.])+\.([A-Za-z]{2,4})$/;
    if (!emailRegex.test(email)) {
        document.getElementById("email-valid").style.display = "inline";
        document.getElementById("email-valid").innerText = "Invalid Email Address";
        document.getElementById("signup-email").classList.add("is-invalid");
        document.querySelector("#email-label").scrollIntoView();  // Email Address label
        return false;
    }

    // verify passwords match
    if (password !== confirmPassword) {
        // deal with password first
        document.getElementById("password-valid").style.display = "inline";
        document.getElementById("password-valid").innerText = "Passwords do not match";
        document.getElementById("signup-password").classList.add("is-invalid");
        document.querySelector("#signup-password").classList.remove("is-valid");
        document.querySelector("#password-label").scrollIntoView();

        // then the confirm password
        document.getElementById("conPassword-valid").style.display = "inline";
        document.getElementById("conPassword-valid").innerText = "Passwords do not match";
        document.getElementById("signup-confirm-password").classList.add("is-invalid");
        document.querySelector("#signup-confirm-password").classList.remove("is-valid");

        return false;
    }

    return true;
}

/**
 * Resests all inputs to have valid and no errors
 */
function resetInputs() {
    // first name
    document.querySelector("#signup-first").classList.remove("is-invalid")
    document.querySelector("#signup-first").classList.add("is-valid")
    // Last name
    document.querySelector("#signup-last").classList.remove("is-invalid")
    document.querySelector("#signup-last").classList.add("is-valid")
    // Email
    document.querySelector("#signup-email").classList.remove("is-invalid")
    document.querySelector("#signup-email").classList.add("is-valid")
    // Password
    document.querySelector("#signup-password").classList.remove("is-invalid")
    document.querySelector("#signup-password").classList.add("is-valid")
    // confirm password
    document.querySelector("#signup-confirm-password").classList.remove("is-invalid")
    document.querySelector("#signup-confirm-password").classList.add("is-valid")
    // phone
    document.querySelector("#signup-phone").classList.remove("is-invalid")
    document.querySelector("#signup-phone").classList.add("is-valid")
    // company name
    document.querySelector("#signup-company").classList.remove("is-invalid")
    document.querySelector("#signup-company").classList.add("is-valid")
    // company Address
    document.querySelector("#signup-address").classList.remove("is-invalid")
    document.querySelector("#signup-address").classList.add("is-valid")
    // postal code
    document.querySelector("#signup-postal").classList.remove("is-invalid")
    document.querySelector("#signup-postal").classList.add("is-valid")
    // city
    document.querySelector("#signup-city").classList.remove("is-invalid")
    document.querySelector("#signup-city").classList.add("is-valid")
    /*// Province
    document.querySelector("#signup-province").classList.remove("is-invalid")
    document.querySelector("#signup-province").classList.add("is-valid")*/
    // country
    document.querySelector("#signup-country").classList.remove("is-invalid")
    document.querySelector("#signup-country").classList.add("is-valid")
    // lang
    document.querySelector("#signup-lang").classList.remove("is-invalid")
    document.querySelector("#signup-lang").classList.add("is-valid")
}