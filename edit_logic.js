let currentQuestionIndex = 0;

function showQuestion(index) {
    const question = testData.questions[index];
    const container = document.getElementById("question-container");
    container.innerHTML = "";

    // ====== عرض نص السؤال بشكل Justified ======
    const questionTitle = document.createElement("h3");
    questionTitle.innerHTML = `<div class="justified-text"><strong>Question ${index + 1}:</strong><br>${question.QuestionText || "Edit question text"}</div>`;
    container.appendChild(questionTitle);

    const optionsContainer = document.createElement("div");
    optionsContainer.style.marginTop = "15px";

    // ====== إذا كان السؤال MCQ ======
    if (question.type === "mcq") {
        question.options.forEach((opt) => {
            const wrapper = document.createElement("div");
            wrapper.style.marginBottom = "10px";

            const radio = document.createElement("input");
            radio.type = "radio";
            radio.name = "correctAnswer";
            radio.checked = opt.IsCorrect;
            radio.onclick = () => {
                question.options.forEach(o => o.IsCorrect = false);
                opt.IsCorrect = true;
            };

            const textInput = document.createElement("input");
            textInput.type = "text";
            textInput.value = opt.OptionText || "";
            textInput.placeholder = "Edit option text";
            textInput.style.marginLeft = "10px";
            textInput.style.width = "60%";
            textInput.oninput = (e) => {
                opt.OptionText = e.target.value;
            };

            wrapper.appendChild(radio);
            wrapper.appendChild(textInput);
            optionsContainer.appendChild(wrapper);
        });

    // ====== إذا كان السؤال Grid-In ======
    } else if (question.type === "grid-in") {
        const correctOption = question.options.find(opt => opt.IsCorrect === true);
        const correctAnswer = correctOption && correctOption.OptionText !== null
            ? correctOption.OptionText
            : "";

        const label = document.createElement("label");
        label.innerText = "Correct Answer:";
        label.style.display = "block";
        label.style.marginTop = "10px";

        const input = document.createElement("input");
        input.type = "text";
        input.placeholder = "Enter correct answer";
        input.value = correctAnswer;
        input.style.width = "100%";
        input.style.padding = "8px";

        input.oninput = (e) => {
            if (correctOption) correctOption.OptionText = e.target.value;
        };

        optionsContainer.appendChild(label);
        optionsContainer.appendChild(input);
    }

    container.appendChild(optionsContainer);
    document.getElementById("next-btn").style.display = "block";
}

function nextQuestion() {
    if (currentQuestionIndex < testData.questions.length - 1) {
        currentQuestionIndex++;
        showQuestion(currentQuestionIndex);
    } else {
        alert("✅ You've reached the last question. You can now save your changes.");
        document.getElementById("next-btn").style.display = "none";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    showQuestion(currentQuestionIndex);
});
