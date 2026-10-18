// password_toggle.js
// Toggles a password input between hidden and visible text,
// swapping the eye / eye-off icon inside the button that triggered it.

function togglePasswordVisibility(button) {
    var wrapper = button.closest(".password-field");
    var input = wrapper.querySelector("input");
    var isCurrentlyHidden = input.type === "password";

    input.type = isCurrentlyHidden ? "text" : "password";
    button.classList.toggle("is-visible", isCurrentlyHidden);
    button.setAttribute(
        "aria-label",
        isCurrentlyHidden ? "Hide password" : "Show password"
    );
}