document.addEventListener("DOMContentLoaded", function () {
    const inputField = document.getElementById("myInput");
    const button = document.querySelector("button");

    // Enable Enter key to trigger button click when input is focused
    inputField.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault(); // Prevent default form behavior
            button.click(); // Simulate button click
        }
    });

    // Enable keyboard navigation for the button (optional but useful for accessibility)
    button.setAttribute("tabindex", "0");

    button.addEventListener("keypress", function (event) {
        if (event.key === "Enter" || event.key === " ") { // Space or Enter triggers click
            event.preventDefault();
            button.click();
        }
    });

    // Voice synthesis logic
    let voices = [];

    function loadVoices() {
        voices = speechSynthesis.getVoices();
        if (voices.length > 0) {
            console.log("Voices loaded");
        }
    }

    // Ensure voices are loaded
    loadVoices();
    speechSynthesis.addEventListener("voiceschanged", loadVoices);

    function speak(text) {
        if (voices.length === 0) {
            console.log("Voices not loaded yet. Trying again...");
            setTimeout(() => speak(text), 500); // Retry after a short delay
            return;
        }

        // Create a SpeechSynthesisUtterance
        const utterance = new SpeechSynthesisUtterance(text);

        // Select a voice safely
        utterance.voice = voices[23] || voices[0]; // Fallback to first available voice
        utterance.pitch = 0.6;
        utterance.rate = 0.9;

        // Speak the text
        speechSynthesis.speak(utterance);
    }

    function getText() {
        const inputElement = document.getElementById("myInput");
        const text = inputElement.value;
        speak(text);
    }

    // Assign button click event
    button.addEventListener("click", getText);
});
