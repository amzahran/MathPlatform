document.addEventListener('DOMContentLoaded', function() {
    // تهيئة المتغيرات العامة
    let currentType = '';
    let questionCountSection1 = 0;
    let questionCountSection2 = 0;
    let currentSection = 1;
    let currentQuestionIndex = 0;
    let testQuestions = [];
    let userAnswers = [];
    let editTestId = null;

    // دالة عرض الاختبار
    function showQuiz(type) {
        currentType = type;
        const quizSection = document.getElementById('quizSection');
        if (quizSection) quizSection.classList.add('active');
        updateQuizTitle();
        
        const urlParams = new URLSearchParams(window.location.search);
        editTestId = urlParams.get('id');
        if (editTestId) {
            const quizTitle = document.getElementById('quizTitle');
            if (quizTitle) quizTitle.textContent = 'تعديل الاختبار';
            loadTestForEdit(editTestId);
        }
    }

    // دالة تحميل الاختبار للتعديل
    async function loadTestForEdit(testId) {
        try {
            const response = await fetch(`get_test.php?id=${testId}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const result = await response.json();
            console.log("Test data loaded:", result);
            
            if (!result.success) {
                throw new Error(result.message || 'فشل تحميل الاختبار');
            }

            const test = result.data;
            // ... بقية كود التحميل
        } catch (error) {
            console.error('Error loading test:', error);
            alert('فشل تحميل الاختبار: ' + error.message);
        }
    }

    // دالة حفظ الاختبار
    async function saveTest(type, testData = null, testId = null) {
        try {
            // التحقق من وجود العناصر المطلوبة
            const testTitle = document.getElementById('testTitle')?.value || '';
            const questionCountSection1 = parseInt(document.getElementById('questionCountSection1')?.value) || 0;
            const durationSection1 = parseInt(document.getElementById('durationSection1')?.value) || 0;
            const questionCountSection2 = parseInt(document.getElementById('questionCountSection2')?.value) || 0;
            const durationSection2 = parseInt(document.getElementById('durationSection2')?.value) || 0;
            const breakDuration = parseInt(document.getElementById('breakDuration')?.value) || 0;
            
            const testTypeRadio = document.querySelector('input[name="testType"]:checked');
            const testType = testTypeRadio ? testTypeRadio.value : 'default';

            // جمع بيانات الأقسام
            const sections = [];
            if (questionCountSection1 > 0) {
                sections.push({
                    sectionNumber: 1,
                    questionCount: questionCountSection1,
                    duration: durationSection1,
                    questions: []
                });
            }
            if (questionCountSection2 > 0) {
                sections.push({
                    sectionNumber: 2,
                    questionCount: questionCountSection2,
                    duration: durationSection2,
                    questions: []
                });
            }

            // جمع الأسئلة من DOM
            const questionElements = document.querySelectorAll('.question-container');
            questionElements.forEach((element) => {
                const questionType = element.querySelector('.question-type')?.value || 'multipleChoice';
                const questionText = element.querySelector('.question-text')?.value || '';
                const questionImage = element.querySelector('.question-image')?.files[0]?.name || '';
                const correctAnswer = element.querySelector('.correct-answer')?.value || '';

                const questionData = {
                    questionType: questionType,
                    questionText: questionText,
                    questionImage: questionImage,
                    correctAnswer: correctAnswer,
                    options: []
                };

                if (questionType === 'multipleChoice') {
                    const optionElements = element.querySelectorAll('.answer-option input[type="text"]');
                    optionElements.forEach((opt, index) => {
                        questionData.options.push(opt.value || `Option ${index + 1}`);
                    });
                }

                // إضافة السؤال إلى القسم المناسب
                if (questionCountSection1 > 0) sections[0].questions.push(questionData);
                if (questionCountSection2 > 0 && sections.length > 1) sections[1].questions.push(questionData);
            });

            // إعداد بيانات الاختبار
            const testData = {
                testTitle: testTitle,
                testType: testType,
                breakDuration: breakDuration,
                sections: sections
            };

            // إرسال البيانات إلى save_test.php
            const response = await fetch('save_test.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(testData)
            });

            const result = await response.json();
            if (result.status === 'success') {
                alert('تم حفظ الاختبار بنجاح');
                console.log('Test saved:', result);
            } else {
                throw new Error(result.message || 'فشل حفظ الاختبار');
            }
        } catch (error) {
            console.error('Error saving test:', error);
            alert('فشل حفظ الاختبار: ' + error.message);
        }
    }

    // إضافة مستمع للحدث عند الضغط على زر "Save Test"
    document.getElementById('saveTestButton').addEventListener('click', function() {
        saveTest(currentType);
    });

    // دالة تحديث عنوان الاختبار
    function updateQuizTitle() {
        const quizTitle = document.getElementById('quizTitle');
        if (quizTitle) quizTitle.textContent = `Create Test - ${currentType}`;
    }
});