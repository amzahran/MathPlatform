let currentQuestion = 1;
let optionCount = 4;
let totalQuestions = 5;
let correctAnswerIndex = -1;

const sectionData = JSON.parse(localStorage.getItem('currentSection'));
if (sectionData) {
  totalQuestions = parseInt(sectionData.questionCount);
}

function updateQuestionText() {
  const questionTitle = document.getElementById("questionTitle");
  questionTitle.innerHTML = `Question ${currentQuestion} of ${totalQuestions}`;
  document.title = `Question ${currentQuestion} of ${totalQuestions}`;
}

function updateButtonsBasedOnSection() {
  const sectionNumber = new URLSearchParams(window.location.search).get('section');
  const saveQuestionBtn = document.getElementById('saveQuestionBtn');
  const nextSectionBtn = document.getElementById('nextSectionBtn');
  const nextBtn = document.getElementById('nextBtn');

  if (sectionNumber === '1') {
    saveQuestionBtn.style.display = 'inline-block';
    nextSectionBtn.style.display = 'inline-block';
    nextBtn.style.display = 'inline-block';
  } else if (sectionNumber === '2') {
    saveQuestionBtn.style.display = 'inline-block';
    nextSectionBtn.style.display = 'none';
    nextBtn.style.display = 'none';
  }
}

function saveQuestion() {
  console.log("Save Question button clicked!");
  const sectionID = localStorage.getItem('SectionID') || 1;
  const questionText = document.getElementById("questionText").value;
  const questionType = document.getElementById("questionType").value;
  const score = document.getElementById("score").value || 15;
  const explanation = document.getElementById("explanation").value;

  if (!questionText) {
    alert("Please enter the question text!");
    return;
  }
  if (questionType === "MCQ" && correctAnswerIndex === -1) {
    alert("Please select the correct answer for the multiple-choice question!");
    return;
  }
  if (questionType === "GridIn" && !document.getElementById("gridInAnswer")?.value) {
    alert("Please enter the correct answer for the Grid-In question!");
    return;
  }

  const formData = new FormData();
  formData.append("sectionID", sectionID);
  formData.append("questionID", "");
  formData.append("questionText", questionText);
  formData.append("questionType", questionType);
  formData.append("score", score);
  formData.append("explanation", explanation);

  const questionImage = document.getElementById("questionImage").files[0];
  if (questionImage) {
    formData.append("questionImage", questionImage);
  }

  const explanationImage = document.getElementById("explanationImage").files[0];
  if (explanationImage) {
    formData.append("explanationImage", explanationImage);
  }

  if (questionType === "MCQ") {
    const options = [];
    for (let i = 0; i < optionCount; i++) {
      const optionText = document.getElementById(`option${i}`).value || "";
      const optionImage = document.getElementById(`optionImage${i}`).files[0];
      if (!optionText) {
        alert(`Please enter text for option ${i + 1}!`);
        return;
      }
      const option = { OptionText: optionText };
      if (optionImage) {
        formData.append(`optionImage${i}`, optionImage);
        option.OptionImage = `optionImage${i}`;
      }
      options.push(option);
    }
    formData.append("Options", JSON.stringify(options));
    formData.append("correctAnswerIndex", correctAnswerIndex);
  } else if (questionType === "GridIn") {
    const gridAnswer = document.getElementById("gridInAnswer").value || "";
    formData.append("GridAnswer", gridAnswer);
  }

  fetch("save_question.php", {
    method: "POST",
    body: formData
  })
    .then(response => {
      console.log("Server response:", response);
      return response.json();
    })
    .then(result => {
      console.log("Server result:", result);
      if (result.status === "success") {
        alert("✅ Question saved successfully!");
      } else {
        alert("❌ Failed to save: " + result.message);
      }
    })
    .catch(error => {
      console.error("❌ Fetch error:", error);
      alert("An error occurred while saving.");
    });
}

function addOption() {
  optionCount++;
  const mcqOptions = document.getElementById("mcqOptions");
  const newOption = document.createElement("div");
  newOption.innerHTML = `
    <input type="text" id="option${optionCount - 1}">
    <input type="file" id="optionImage${optionCount - 1}" accept="image/*">
    <img id="optionPreview${optionCount - 1}" src="" style="display: none;">
    <button onclick="setCorrectAnswer(${optionCount - 1})">Set as Correct</button>
  `;
  mcqOptions.appendChild(newOption);

  document.getElementById(`optionImage${optionCount - 1}`).addEventListener('change', () => {
    const img = document.getElementById(`optionPreview${optionCount - 1}`);
    if (document.getElementById(`optionImage${optionCount - 1}`).files[0]) {
      img.src = URL.createObjectURL(document.getElementById(`optionImage${optionCount - 1}`).files[0]);
      img.style.display = 'block';
    } else {
      img.style.display = 'none';
    }
    renderPreview();
  });
}

function goToNextSection() {
  window.location.href = "nextSection.html";
}

function goToPrevious() {
  if (currentQuestion > 1) {
    currentQuestion--;
    updateQuestionText();
    renderPreview();
    enableDisableButtons();
  }
}

function goToNext() {
  if (currentQuestion < totalQuestions) {
    currentQuestion++;
    updateQuestionText();
    renderPreview();
    enableDisableButtons();
  }
}

function enableDisableButtons() {
  const previousBtn = document.getElementById("previousBtn");
  const nextBtn = document.getElementById("nextBtn");
  const saveTestBtn = document.getElementById("saveTestBtn");
  const nextSectionBtn = document.getElementById("nextSectionBtn");

  previousBtn.disabled = currentQuestion === 1;
  saveTestBtn.style.display = currentQuestion === 1 ? 'none' : 'inline-block';
  nextBtn.disabled = currentQuestion === totalQuestions;
  nextSectionBtn.style.display = currentQuestion === totalQuestions ? 'inline-block' : 'none';
}

function renderPreview() {
  const questionText = document.getElementById('questionText').value;
  const explanation = document.getElementById('explanation').value;
  const score = document.getElementById('score').value || 15;
  const questionType = document.getElementById('questionType').value;
  const previewBox = document.getElementById('preview');
  let html = '<strong>Question Preview:</strong><br>';

  html += '<p><strong>Question Text:</strong><br>' + (questionText || "No question text entered") + '</p>';

  const questionImage = document.getElementById('questionImage').files[0];
  if (questionImage) {
    const imgURL = URL.createObjectURL(questionImage);
    html += '<img src="' + imgURL + '" class="preview-image"><br>';
  }

  html += "<strong>Explanation:</strong><p>" + (explanation || "No explanation provided") + "</p>";
  const explanationImage = document.getElementById("explanationImage").files[0];
  if (explanationImage) {
    const expImgURL = URL.createObjectURL(explanationImage);
    html += '<img src="' + expImgURL + '" class="preview-image"><br>';
  }

  if (questionType === "MCQ") {
    html += "<strong>Options:</strong><br>";
    for (let i = 0; i < optionCount; i++) {
      const optionText = document.getElementById(`option${i}`)?.value || "";
      const optionImage = document.getElementById(`optionImage${i}`)?.files[0];
      html += `<p>Option ${String.fromCharCode(65 + i)}: ${optionText || "No option text"}</p>`;
      
      if (optionImage) {
        const optionImgURL = URL.createObjectURL(optionImage);
        html += `<img src="${optionImgURL}" class="preview-image" style="max-width:200px;margin-top:5px;"><br>`;
      }
    }

    if (correctAnswerIndex !== -1) {
      html += `<strong>Correct Answer:</strong><p>Option ${String.fromCharCode(65 + correctAnswerIndex)}</p>`;
    } else {
      html += `<strong>Correct Answer:</strong><p>No answer provided</p>`;
    }
  } else if (questionType === "GridIn") {
    const gridAnswer = document.getElementById("gridInAnswer")?.value || "";
    html += `<strong>Correct Answer:</strong><p>${gridAnswer || "No answer provided"}</p>`;
  }
  
  previewBox.innerHTML = html;
}

function setCorrectAnswer(index) {
  correctAnswerIndex = index;
  renderPreview();
}

function toggleAnswerBlock() {
  const questionType = document.getElementById('questionType').value;
  document.getElementById('mcqOptions').style.display = questionType === "MCQ" ? "block" : "none";
  document.getElementById('gridInAnswerBlock').style.display = questionType === "Grid-In" ? "block" : "none";
  renderPreview();
}

document.addEventListener("DOMContentLoaded", function () {
  updateQuestionText();
  enableDisableButtons();
  renderPreview();
  updateButtonsBasedOnSection();
  const saveQuestionBtn = document.getElementById("saveQuestionBtn");
  if (saveQuestionBtn) {
    console.log("Save Question button found!");
    saveQuestionBtn.removeEventListener("click", saveQuestion);
    saveQuestionBtn.addEventListener("click", saveQuestion);
  } else {
    console.error("Save Question button not found with ID saveQuestionBtn");
  }

  ['questionImage', 'explanationImage', 'optionImage0', 'optionImage1', 'optionImage2', 'optionImage3'].forEach(id => {
    const input = document.getElementById(id);
    input?.addEventListener('change', () => {
      const img = document.getElementById(id.replace('Image', 'ImageDisplay') || id.replace('Image', 'Preview'));
      if (input.files[0]) {
        img.src = URL.createObjectURL(input.files[0]);
        img.style.display = 'block';
      } else {
        img.style.display = 'none';
      }
      renderPreview();
    });
  });
});