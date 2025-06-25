// Frontend JavaScript for AI Quiz System
jQuery(document).ready(function($) {
    console.log('AI Quiz System frontend loaded');
    console.log('aiqs_data:', aiqs_data);
    
    // Debug exam and subject selection
    $('#aiqs-exam-select').on('change', function() {
        var examId = $(this).val();
        console.log('Exam selected:', examId);
        
        if (examId) {
            // Load subjects for this exam
            loadSubjectsForExam(examId);
        } else {
            $('#aiqs-subjects-container').empty();
            $('#aiqs-start-quiz-btn').prop('disabled', true);
        }
    });
    
    function loadSubjectsForExam(examId) {
        console.log('Loading subjects for exam:', examId);
        
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_get_exam_subjects',
                exam_id: examId,
                nonce: aiqs_data.nonce
            },
            success: function(response) {
                console.log('Subjects response:', response);
                
                if (response.success) {
                    displaySubjects(response.data);
                } else {
                    console.error('Error loading subjects:', response.data);
                    $('#aiqs-subjects-container').html('<p class="aiqs-error">Error loading subjects: ' + response.data.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading subjects:', error);
                $('#aiqs-subjects-container').html('<p class="aiqs-error">Network error loading subjects</p>');
            }
        });
    }
    
    function displaySubjects(subjects) {
        console.log('Displaying subjects:', subjects);
        
        if (!subjects || subjects.length === 0) {
            $('#aiqs-subjects-container').html('<p class="aiqs-error">No subjects found for this exam</p>');
            return;
        }
        
        var html = '<div class="aiqs-subjects-list">';
        html += '<h3>Select Subjects:</h3>';
        
        subjects.forEach(function(subject) {
            html += '<label class="aiqs-subject-checkbox">';
            html += '<input type="checkbox" name="subjects[]" value="' + subject.id + '">';
            html += '<span>' + subject.name + '</span>';
            html += '</label>';
        });
        
        html += '</div>';
        html += '<div class="aiqs-question-source">';
        html += '<h3>Question Source:</h3>';
        html += '<select id="aiqs-question-source">';
        html += '<option value="">Use Default Setting</option>';
        html += '<option value="ai_generated">AI Generated Questions</option>';
        html += '<option value="stored">Stored Questions</option>';
        html += '<option value="demo">Demo Questions</option>';
        html += '</select>';
        html += '</div>';
        
        $('#aiqs-subjects-container').html(html);
        
        // Enable start button when subjects are selected
        $(document).on('change', 'input[name="subjects[]"]', function() {
            var selectedCount = $('input[name="subjects[]"]:checked').length;
            $('#aiqs-start-quiz-btn').prop('disabled', selectedCount === 0);
        });
    }
    
    // Handle quiz start
    $('#aiqs-start-quiz-btn').on('click', function() {
        var examId = $('#aiqs-exam-select').val();
        var selectedSubjects = [];
        var questionSource = $('#aiqs-question-source').val();
        
        $('input[name="subjects[]"]:checked').each(function() {
            selectedSubjects.push($(this).val());
        });
        
        console.log('Starting quiz with:', {
            examId: examId,
            subjects: selectedSubjects,
            questionSource: questionSource
        });
        
        if (!examId || selectedSubjects.length === 0) {
            alert('Please select an exam and at least one subject');
            return;
        }
        
        // Show loading state
        $(this).prop('disabled', true).text('Starting Quiz...');
        
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_start_quiz',
                exam_id: examId,
                subject_ids: selectedSubjects,
                question_source: questionSource,
                nonce: aiqs_data.nonce
            },
            success: function(response) {
                console.log('Quiz start response:', response);
                
                if (response.success) {
                    // Hide selection interface and show quiz
                    $('#aiqs-quiz-selection').hide();
                    displayQuiz(response.data);
                } else {
                    console.error('Error starting quiz:', response.data);
                    alert('Error starting quiz: ' + response.data.message);
                    $('#aiqs-start-quiz-btn').prop('disabled', false).text('Start Quiz');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error starting quiz:', error);
                alert('Network error starting quiz');
                $('#aiqs-start-quiz-btn').prop('disabled', false).text('Start Quiz');
            }
        });
    });
    
    function displayQuiz(quizData) {
        console.log('Displaying quiz:', quizData);
        
        if (!quizData.question) {
            $('#aiqs-quiz-container').html('<p class="aiqs-error">No questions available</p>');
            return;
        }
        
        var html = '<div class="aiqs-quiz-interface">';
        html += '<div class="aiqs-quiz-header">';
        html += '<h2>' + quizData.exam.name + '</h2>';
        html += '<div class="aiqs-quiz-progress">';
        html += '<span>Question ' + quizData.current_question + ' of ' + quizData.total_questions + '</span>';
        html += '<div class="aiqs-progress-bar">';
        html += '<div class="aiqs-progress-fill" style="width: ' + (quizData.current_question / quizData.total_questions * 100) + '%"></div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        html += '<div class="aiqs-question-container">';
        html += '<div class="aiqs-question-text">' + quizData.question.question + '</div>';
        
        if (quizData.question.image_url) {
            html += '<div class="aiqs-question-image"><img src="' + quizData.question.image_url + '" alt="Question image"></div>';
        }
        
        html += '<div class="aiqs-question-options">';
        html += '<label><input type="radio" name="answer" value="A"> A. ' + quizData.question.option_a + '</label>';
        html += '<label><input type="radio" name="answer" value="B"> B. ' + quizData.question.option_b + '</label>';
        html += '<label><input type="radio" name="answer" value="C"> C. ' + quizData.question.option_c + '</label>';
        html += '<label><input type="radio" name="answer" value="D"> D. ' + quizData.question.option_d + '</label>';
        html += '</div>';
        
        html += '<div class="aiqs-question-actions">';
        html += '<button id="aiqs-submit-answer" class="aiqs-button" disabled>Submit Answer</button>';
        html += '</div>';
        html += '</div>';
        
        html += '</div>';
        
        $('#aiqs-quiz-container').html(html);
        
        // Store quiz session data
        window.aiqs_current_quiz = {
            session_id: quizData.session_id,
            question_id: quizData.question.id,
            start_time: Date.now()
        };
        
        // Enable submit button when answer is selected
        $('input[name="answer"]').on('change', function() {
            $('#aiqs-submit-answer').prop('disabled', false);
        });
        
        // Handle answer submission
        $('#aiqs-submit-answer').on('click', function() {
            var answer = $('input[name="answer"]:checked').val();
            var timeSpent = Math.round((Date.now() - window.aiqs_current_quiz.start_time) / 1000);
            
            if (!answer) {
                alert('Please select an answer');
                return;
            }
            
            console.log('Submitting answer:', {
                session_id: window.aiqs_current_quiz.session_id,
                question_id: window.aiqs_current_quiz.question_id,
                answer: answer,
                time_spent: timeSpent
            });
            
            // Show loading state
            $(this).prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: aiqs_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiqs_submit_answer',
                    session_id: window.aiqs_current_quiz.session_id,
                    question_id: window.aiqs_current_quiz.question_id,
                    answer: answer,
                    time_spent: timeSpent,
                    nonce: aiqs_data.nonce
                },
                success: function(response) {
                    console.log('Answer submission response:', response);
                    
                    if (response.success) {
                        // Show answer feedback
                        showAnswerFeedback(response.data);
                    } else {
                        console.error('Error submitting answer:', response.data);
                        alert('Error submitting answer: ' + response.data.message);
                        $('#aiqs-submit-answer').prop('disabled', false).text('Submit Answer');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error submitting answer:', error);
                    alert('Network error submitting answer');
                    $('#aiqs-submit-answer').prop('disabled', false).text('Submit Answer');
                }
            });
        });
    }
    
    function showAnswerFeedback(data) {
        console.log('Showing answer feedback:', data);
        
        var html = '<div class="aiqs-answer-feedback">';
        html += '<div class="aiqs-feedback-result ' + (data.is_correct ? 'correct' : 'incorrect') + '">';
        html += data.is_correct ? '✓ Correct!' : '✗ Incorrect';
        html += '</div>';
        
        if (!data.is_correct) {
            html += '<div class="aiqs-correct-answer">Correct answer: ' + data.correct_answer + '</div>';
        }
        
        if (data.explanation) {
            html += '<div class="aiqs-explanation">' + data.explanation + '</div>';
        }
        
        html += '<div class="aiqs-feedback-actions">';
        
        if (data.has_next) {
            html += '<button id="aiqs-next-question" class="aiqs-button">Next Question</button>';
        } else {
            html += '<button id="aiqs-finish-quiz" class="aiqs-button">Finish Quiz</button>';
        }
        
        html += '</div>';
        html += '</div>';
        
        $('.aiqs-question-container').html(html);
        
        // Handle next question
        $('#aiqs-next-question').on('click', function() {
            if (data.next_question) {
                // Update quiz data and display next question
                var nextQuizData = {
                    session_id: window.aiqs_current_quiz.session_id,
                    question: data.next_question,
                    current_question: data.current_question,
                    total_questions: data.total_questions
                };
                
                displayNextQuestion(nextQuizData);
            }
        });
        
        // Handle quiz finish
        $('#aiqs-finish-quiz').on('click', function() {
            finishQuiz();
        });
    }
    
    function displayNextQuestion(quizData) {
        console.log('Displaying next question:', quizData);
        
        // Update progress
        $('.aiqs-quiz-progress span').text('Question ' + quizData.current_question + ' of ' + quizData.total_questions);
        $('.aiqs-progress-fill').css('width', (quizData.current_question / quizData.total_questions * 100) + '%');
        
        // Display new question
        var html = '<div class="aiqs-question-text">' + quizData.question.question + '</div>';
        
        if (quizData.question.image_url) {
            html += '<div class="aiqs-question-image"><img src="' + quizData.question.image_url + '" alt="Question image"></div>';
        }
        
        html += '<div class="aiqs-question-options">';
        html += '<label><input type="radio" name="answer" value="A"> A. ' + quizData.question.option_a + '</label>';
        html += '<label><input type="radio" name="answer" value="B"> B. ' + quizData.question.option_b + '</label>';
        html += '<label><input type="radio" name="answer" value="C"> C. ' + quizData.question.option_c + '</label>';
        html += '<label><input type="radio" name="answer" value="D"> D. ' + quizData.question.option_d + '</label>';
        html += '</div>';
        
        html += '<div class="aiqs-question-actions">';
        html += '<button id="aiqs-submit-answer" class="aiqs-button" disabled>Submit Answer</button>';
        html += '</div>';
        
        $('.aiqs-question-container').html(html);
        
        // Update session data
        window.aiqs_current_quiz.question_id = quizData.question.id;
        window.aiqs_current_quiz.start_time = Date.now();
        
        // Re-bind event handlers
        $('input[name="answer"]').on('change', function() {
            $('#aiqs-submit-answer').prop('disabled', false);
        });
        
        $('#aiqs-submit-answer').on('click', function() {
            var answer = $('input[name="answer"]:checked').val();
            var timeSpent = Math.round((Date.now() - window.aiqs_current_quiz.start_time) / 1000);
            
            if (!answer) {
                alert('Please select an answer');
                return;
            }
            
            $(this).prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: aiqs_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'aiqs_submit_answer',
                    session_id: window.aiqs_current_quiz.session_id,
                    question_id: window.aiqs_current_quiz.question_id,
                    answer: answer,
                    time_spent: timeSpent,
                    nonce: aiqs_data.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showAnswerFeedback(response.data);
                    } else {
                        alert('Error submitting answer: ' + response.data.message);
                        $('#aiqs-submit-answer').prop('disabled', false).text('Submit Answer');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Network error submitting answer');
                    $('#aiqs-submit-answer').prop('disabled', false).text('Submit Answer');
                }
            });
        });
    }
    
    function finishQuiz() {
        console.log('Finishing quiz');
        
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_finish_quiz',
                session_id: window.aiqs_current_quiz.session_id,
                nonce: aiqs_data.nonce
            },
            success: function(response) {
                console.log('Quiz finish response:', response);
                
                if (response.success) {
                    displayQuizResults(response.data);
                } else {
                    alert('Error finishing quiz: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Network error finishing quiz');
            }
        });
    }
    
    function displayQuizResults(results) {
        console.log('Displaying quiz results:', results);
        
        var html = '<div class="aiqs-quiz-results">';
        html += '<h2>Quiz Results</h2>';
        html += '<div class="aiqs-score-display">';
        html += '<div class="aiqs-score-circle">';
        html += '<span class="aiqs-score-value">' + Math.round(results.score) + '%</span>';
        html += '</div>';
        html += '</div>';
        
        html += '<div class="aiqs-results-summary">';
        html += '<div class="aiqs-result-item">';
        html += '<span class="aiqs-result-label">Correct Answers:</span>';
        html += '<span class="aiqs-result-value">' + results.correct_answers + ' / ' + results.total_questions + '</span>';
        html += '</div>';
        html += '<div class="aiqs-result-item">';
        html += '<span class="aiqs-result-label">Time Taken:</span>';
        html += '<span class="aiqs-result-value">' + Math.floor(results.time_taken / 60) + 'm ' + (results.time_taken % 60) + 's</span>';
        html += '</div>';
        html += '<div class="aiqs-result-item">';
        html += '<span class="aiqs-result-label">Status:</span>';
        html += '<span class="aiqs-result-value ' + (results.passed ? 'passed' : 'failed') + '">';
        html += results.passed ? 'Passed' : 'Failed';
        html += '</span>';
        html += '</div>';
        html += '</div>';
        
        html += '<div class="aiqs-results-actions">';
        html += '<button id="aiqs-retake-quiz" class="aiqs-button">Retake Quiz</button>';
        html += '<button id="aiqs-view-performance" class="aiqs-button secondary">View Performance</button>';
        html += '</div>';
        
        html += '</div>';
        
        $('#aiqs-quiz-container').html(html);
        
        // Handle retake quiz
        $('#aiqs-retake-quiz').on('click', function() {
            location.reload();
        });
        
        // Handle view performance
        $('#aiqs-view-performance').on('click', function() {
            // This would navigate to performance page or load performance data
            alert('Performance tracking feature coming soon!');
        });
    }
});