/**
 * Enhanced Frontend JavaScript for AI Quiz System
 * Replace your existing public/js/ai-quiz-system-public.js
 */

(function($) {
    'use strict';
    
    // Global quiz state
    window.aiqs_quiz_state = {
        session_id: null,
        current_question: 0,
        total_questions: 0,
        questions: [],
        answers: {},
        start_time: null,
        timer_interval: null
    };
    
    // Global chatbot state
    window.aiqs_chatbot_state = {
        session_id: null,
        conversation_id: null,
        message_history: [],
        is_typing: false
    };
    
    console.log('AI Quiz System frontend loaded');
    console.log('aiqs_data:', aiqs_data);
    
    $(document).ready(function() {
        initializeQuizInterface();
        initializeChatbotInterface();
        initializePerformanceInterface();
    });
    
    /**
     * Initialize Quiz Interface
     */
    function initializeQuizInterface() {
        // Exam selection handler
        $('#aiqs-exam-select').on('change', function() {
            var examId = $(this).val();
            console.log('Exam selected:', examId);
            
            // Clear previous subjects and disable start button
            $('#aiqs-subjects-container').empty();
            $('#aiqs-start-quiz-btn').prop('disabled', true);
            
            if (examId) {
                loadSubjectsForExam(examId);
            } else {
                showMessage('info', aiqs_data.i18n.select_exam);
            }
        });
        
        // Start quiz button handler
        $('#aiqs-start-quiz-btn').on('click', function(e) {
            e.preventDefault();
            
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
                showMessage('error', aiqs_data.i18n.select_subjects);
                return;
            }
            
            startQuiz(examId, selectedSubjects, questionSource);
        });
    }
    
    /**
     * Load subjects for selected exam
     */
    function loadSubjectsForExam(examId) {
        console.log('Loading subjects for exam:', examId);
        
        showMessage('info', aiqs_data.i18n.loading);
        
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_get_exam_subjects',
                exam_id: examId,
                nonce: aiqs_data.nonce
            },
            timeout: 10000,
            beforeSend: function() {
                $('#aiqs-subjects-container').html('<div class="aiqs-loading">' + aiqs_data.i18n.loading + '</div>');
            },
            success: function(response) {
                console.log('Subjects response:', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    displaySubjects(response.data);
                } else {
                    var message = response.data && response.data.message ? response.data.message : aiqs_data.i18n.no_subjects;
                    $('#aiqs-subjects-container').html('<div class="aiqs-error">' + message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading subjects:', error);
                $('#aiqs-subjects-container').html('<div class="aiqs-error">' + aiqs_data.i18n.network_error + '</div>');
            }
        });
    }
    
    /**
     * Display subjects selection interface
     */
    function displaySubjects(subjects) {
        console.log('Displaying subjects:', subjects);
        
        var html = '<div class="aiqs-subjects-list">';
        html += '<h3>Select Subjects:</h3>';
        html += '<div class="aiqs-subjects-grid">';
        
        subjects.forEach(function(subject) {
            html += '<label class="aiqs-subject-option">';
            html += '<input type="checkbox" name="subjects[]" value="' + subject.id + '">';
            html += '<span class="aiqs-subject-name">' + subject.name + '</span>';
            if (subject.description) {
                html += '<span class="aiqs-subject-description">' + subject.description + '</span>';
            }
            html += '</label>';
        });
        
        html += '</div>';
        html += '</div>';
        
        // Question source selection
        html += '<div class="aiqs-question-source">';
        html += '<h3>Question Source:</h3>';
        html += '<select id="aiqs-question-source">';
        html += '<option value="">Use Default Setting</option>';
        html += '<option value="ai">AI Generated Questions</option>';
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
    
    /**
     * Start quiz
     */
    function startQuiz(examId, subjectIds, questionSource) {
        showMessage('info', aiqs_data.i18n.please_wait);
        $('#aiqs-start-quiz-btn').prop('disabled', true).text('Starting Quiz...');
        
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_start_quiz',
                exam_id: examId,
                subject_ids: subjectIds,
                question_source: questionSource,
                nonce: aiqs_data.nonce
            },
            timeout: 30000,
            success: function(response) {
                console.log('Quiz start response:', response);
                
                if (response.success && response.data) {
                    // Initialize quiz state
                    window.aiqs_quiz_state = {
                        session_id: response.data.session_id,
                        current_question: 0,
                        total_questions: response.data.total_questions,
                        questions: [],
                        answers: {},
                        start_time: Date.now(),
                        timer_interval: null,
                        exam: response.data.exam
                    };
                    
                    // Hide selection interface and show quiz
                    $('#aiqs-quiz-selection').hide();
                    displayQuizInterface(response.data);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : aiqs_data.i18n.error_occurred;
                    showMessage('error', errorMsg);
                    $('#aiqs-start-quiz-btn').prop('disabled', false).text('Start Quiz');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error starting quiz:', error);
                showMessage('error', aiqs_data.i18n.network_error);
                $('#aiqs-start-quiz-btn').prop('disabled', false).text('Start Quiz');
            }
        });
    }
    
    /**
     * Display quiz interface
     */
    function displayQuizInterface(quizData) {
        console.log('Displaying quiz interface:', quizData);
        
        if (!quizData.question) {
            $('#aiqs-quiz-container').html('<div class="aiqs-error">No questions available</div>');
            return;
        }
        
        var html = '<div class="aiqs-quiz-interface">';
        
        // Quiz header
        html += '<div class="aiqs-quiz-header">';
        html += '<h2>' + quizData.exam.name + '</h2>';
        html += '<div class="aiqs-quiz-progress">';
        html += '<span class="aiqs-progress-text">Question ' + quizData.current_question + ' of ' + quizData.total_questions + '</span>';
        html += '<div class="aiqs-progress-bar">';
        html += '<div class="aiqs-progress-fill" style="width: ' + ((quizData.current_question / quizData.total_questions) * 100) + '%"></div>';
        html += '</div>';
        html += '</div>';
        
        // Timer
        if (quizData.exam.time_limit && quizData.exam.time_limit > 0) {
            html += '<div class="aiqs-timer" id="aiqs-timer">Time: <span id="aiqs-timer-display">--:--</span></div>';
        }
        
        html += '</div>';
        
        // Question container
        html += '<div class="aiqs-question-container">';
        html += displayQuestion(quizData.question);
        html += '</div>';
        
        // Quiz actions
        html += '<div class="aiqs-quiz-actions">';
        html += '<button id="aiqs-submit-answer" class="aiqs-button" disabled>Submit Answer</button>';
        html += '<button id="aiqs-finish-quiz" class="aiqs-button secondary">Finish Quiz</button>';
        html += '</div>';
        
        html += '</div>';
        
        $('#aiqs-quiz-container').html(html);
        
        // Store current question
        window.aiqs_quiz_state.current_question_data = quizData.question;
        
        // Start timer if enabled
        if (quizData.exam.time_limit && quizData.exam.time_limit > 0) {
            startQuizTimer(quizData.exam.time_limit);
        }
        
        // Bind event handlers
        bindQuizEventHandlers();
    }
    
    /**
     * Display a single question
     */
    function displayQuestion(question) {
        var html = '<div class="aiqs-question">';
        html += '<div class="aiqs-question-text">' + question.question + '</div>';
        
        // Show image if available
        if (question.image_url) {
            html += '<div class="aiqs-question-image">';
            html += '<img src="' + question.image_url + '" alt="Question Image" class="aiqs-question-img">';
            html += '</div>';
        }
        
        // Options
        html += '<div class="aiqs-question-options">';
        html += '<label class="aiqs-option"><input type="radio" name="answer" value="A"> <span class="aiqs-option-text">A. ' + question.option_a + '</span></label>';
        html += '<label class="aiqs-option"><input type="radio" name="answer" value="B"> <span class="aiqs-option-text">B. ' + question.option_b + '</span></label>';
        html += '<label class="aiqs-option"><input type="radio" name="answer" value="C"> <span class="aiqs-option-text">C. ' + question.option_c + '</span></label>';
        html += '<label class="aiqs-option"><input type="radio" name="answer" value="D"> <span class="aiqs-option-text">D. ' + question.option_d + '</span></label>';
        html += '</div>';
        
        html += '</div>';
        
        return html;
    }
    
    /**
     * Bind quiz event handlers
     */
    function bindQuizEventHandlers() {
        // Enable submit button when answer is selected
        $('input[name="answer"]').on('change', function() {
            $('#aiqs-submit-answer').prop('disabled', false);
        });
        
        // Submit answer
        $('#aiqs-submit-answer').on('click', function() {
            submitAnswer();
        });
        
        // Finish quiz
        $('#aiqs-finish-quiz').on('click', function() {
            if (confirm(aiqs_data.i18n.confirm_submit)) {
                finishQuiz();
            }
        });
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            if (e.key >= '1' && e.key <= '4') {
                var optionIndex = parseInt(e.key) - 1;
                var options = ['A', 'B', 'C', 'D'];
                $('input[name="answer"][value="' + options[optionIndex] + '"]').prop('checked', true).trigger('change');
            }
        });
    }
    
    /**
     * Submit answer
     */
    function submitAnswer() {
        var answer = $('input[name="answer"]:checked').val();
        var questionStartTime = window.aiqs_quiz_state.question_start_time || Date.now();
        var timeSpent = Math.round((Date.now() - questionStartTime) / 1000);
        
        if (!answer) {
            showMessage('error', 'Please select an answer');
            return;
        }
        
        // Store answer locally
        var questionId = window.aiqs_quiz_state.current_question_data.id || ('q_' + window.aiqs_quiz_state.current_question);
        window.aiqs_quiz_state.answers[questionId] = {
            answer: answer,
            time_spent: timeSpent,
            timestamp: Date.now()
        };
        
        console.log('Submitting answer:', {
            session_id: window.aiqs_quiz_state.session_id,
            question_id: questionId,
            answer: answer,
            time_spent: timeSpent
        });
        
        // Disable submit button and show loading
        $('#aiqs-submit-answer').prop('disabled', true).text('Submitting...');
        
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_submit_answer',
                session_id: window.aiqs_quiz_state.session_id,
                question_id: questionId,
                answer: answer,
                time_spent: timeSpent,
                nonce: aiqs_data.nonce
            },
            timeout: 15000,
            success: function(response) {
                console.log('Answer submission response:', response);
                
                if (response.success && response.data) {
                    showAnswerFeedback(response.data);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Error submitting answer';
                    showMessage('error', errorMsg);
                    $('#aiqs-submit-answer').prop('disabled', false).text('Submit Answer');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error submitting answer:', error);
                showMessage('error', aiqs_data.i18n.network_error);
                $('#aiqs-submit-answer').prop('disabled', false).text('Submit Answer');
            }
        });
    }
    
    /**
     * Show answer feedback
     */
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
            html += '<div class="aiqs-explanation">';
            html += '<strong>Explanation:</strong> ' + data.explanation;
            html += '</div>';
        }
        
        html += '<div class="aiqs-feedback-actions">';
        
        if (data.has_next) {
            html += '<button id="aiqs-next-question" class="aiqs-button">Next Question</button>';
        } else {
            html += '<button id="aiqs-finish-quiz-final" class="aiqs-button">Finish Quiz</button>';
        }
        
        html += '</div>';
        html += '</div>';
        
        $('.aiqs-question-container').html(html);
        
        // Update progress
        if (data.current_question && data.total_questions) {
            $('.aiqs-progress-text').text('Question ' + data.current_question + ' of ' + data.total_questions);
            $('.aiqs-progress-fill').css('width', ((data.current_question - 1) / data.total_questions * 100) + '%');
        }
        
        // Handle next question
        $('#aiqs-next-question').on('click', function() {
            if (data.next_question) {
                window.aiqs_quiz_state.current_question++;
                window.aiqs_quiz_state.current_question_data = data.next_question;
                window.aiqs_quiz_state.question_start_time = Date.now();
                
                $('.aiqs-question-container').html(displayQuestion(data.next_question));
                
                // Re-bind event handlers
                bindQuizEventHandlers();
            }
        });
        
        // Handle quiz finish
        $('#aiqs-finish-quiz-final').on('click', function() {
            finishQuiz();
        });
    }
    
    /**
     * Finish quiz
     */
    function finishQuiz() {
        console.log('Finishing quiz');
        
        // Stop timer
        if (window.aiqs_quiz_state.timer_interval) {
            clearInterval(window.aiqs_quiz_state.timer_interval);
        }
        
        showMessage('info', 'Submitting quiz...');
        
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_finish_quiz',
                session_id: window.aiqs_quiz_state.session_id,
                nonce: aiqs_data.nonce
            },
            timeout: 20000,
            success: function(response) {
                console.log('Quiz finish response:', response);
                
                if (response.success && response.data) {
                    displayQuizResults(response.data);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Error finishing quiz';
                    showMessage('error', errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error finishing quiz:', error);
                showMessage('error', aiqs_data.i18n.network_error);
            }
        });
    }
    
    /**
     * Display quiz results
     */
    function displayQuizResults(results) {
        console.log('Displaying quiz results:', results);
        
        var html = '<div class="aiqs-quiz-results">';
        html += '<h2>Quiz Results</h2>';
        
        // Score display
        html += '<div class="aiqs-score-display">';
        html += '<div class="aiqs-score-circle ' + (results.passed ? 'passed' : 'failed') + '">';
        html += '<span class="aiqs-score-value">' + Math.round(results.score) + '%</span>';
        html += '<span class="aiqs-score-status">' + (results.passed ? 'Passed' : 'Failed') + '</span>';
        html += '</div>';
        html += '</div>';
        
        // Results summary
        html += '<div class="aiqs-results-summary">';
        html += '<div class="aiqs-result-item">';
        html += '<span class="aiqs-result-label">Correct Answers:</span>';
        html += '<span class="aiqs-result-value">' + results.correct_answers + ' / ' + results.total_questions + '</span>';
        html += '</div>';
        html += '<div class="aiqs-result-item">';
        html += '<span class="aiqs-result-label">Time Taken:</span>';
        html += '<span class="aiqs-result-value">' + formatTime(results.time_taken) + '</span>';
        html += '</div>';
        html += '<div class="aiqs-result-item">';
        html += '<span class="aiqs-result-label">Accuracy:</span>';
        html += '<span class="aiqs-result-value">' + Math.round(results.score) + '%</span>';
        html += '</div>';
        html += '</div>';
        
        // Action buttons
        html += '<div class="aiqs-results-actions">';
        html += '<button id="aiqs-retake-quiz" class="aiqs-button">Retake Quiz</button>';
        if (aiqs_data.current_user_id) {
            html += '<button id="aiqs-view-performance" class="aiqs-button secondary">View Performance</button>';
        }
        html += '</div>';
        
        html += '</div>';
        
        $('#aiqs-quiz-container').html(html);
        
        // Bind result actions
        $('#aiqs-retake-quiz').on('click', function() {
            location.reload();
        });
        
        $('#aiqs-view-performance').on('click', function() {
            loadPerformanceData();
        });
    }
    
    /**
     * Start quiz timer
     */
    function startQuizTimer(timeLimit) {
        var endTime = Date.now() + (timeLimit * 1000);
        
        window.aiqs_quiz_state.timer_interval = setInterval(function() {
            var remaining = Math.max(0, endTime - Date.now());
            var minutes = Math.floor(remaining / 60000);
            var seconds = Math.floor((remaining % 60000) / 1000);
            
            var display = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            $('#aiqs-timer-display').text(display);
            
            // Change color based on remaining time
            var $timer = $('#aiqs-timer');
            if (remaining < 300000) { // Less than 5 minutes
                $timer.addClass('danger');
            } else if (remaining < 600000) { // Less than 10 minutes
                $timer.addClass('warning');
            }
            
            // Time's up
            if (remaining <= 0) {
                clearInterval(window.aiqs_quiz_state.timer_interval);
                alert(aiqs_data.i18n.time_up);
                finishQuiz();
            }
        }, 1000);
    }
    
    /**
     * Initialize Chatbot Interface
     */
    function initializeChatbotInterface() {
        // Generate session ID for guests
        if (!aiqs_data.current_user_id) {
            window.aiqs_chatbot_state.session_id = 'guest_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }
        
        // Send message handler
        $('#aiqs-chatbot-send, #aiqs-chatbot-container').on('click keypress', '#aiqs-chatbot-send, #aiqs-chatbot-input', function(e) {
            if (e.type === 'click' || (e.type === 'keypress' && e.which === 13)) {
                e.preventDefault();
                sendChatbotMessage();
            }
        });
        
        // Load conversation history for logged-in users
        if (aiqs_data.current_user_id) {
            loadConversationHistory();
        }
    }
    
    /**
     * Send chatbot message
     */
    function sendChatbotMessage() {
        var $input = $('#aiqs-chatbot-input');
        var message = $input.val().trim();
        
        if (!message || window.aiqs_chatbot_state.is_typing) {
            return;
        }
        
        // Add user message to chat
        addChatMessage('user', message);
        $input.val('');
        
        // Show typing indicator
        showTypingIndicator();
        
        console.log('Sending chatbot message:', message);
        
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_chatbot_query',
                query: message,
                session_id: window.aiqs_chatbot_state.session_id,
                nonce: aiqs_data.nonce
            },
            timeout: 30000,
            success: function(response) {
                console.log('Chatbot response:', response);
                
                hideTypingIndicator();
                
                if (response.success && response.data) {
                    addChatMessage('bot', response.data.response);
                    
                    // Store conversation details
                    if (response.data.conversation_id) {
                        window.aiqs_chatbot_state.conversation_id = response.data.conversation_id;
                    }
                    
                    // Show recommendations if available
                    if (response.data.recommendations && response.data.recommendations.length > 0) {
                        showRecommendations(response.data.recommendations);
                    }
                    
                    // Update student insights
                    if (response.data.student_insights) {
                        updateStudentInsights(response.data.student_insights);
                    }
                } else {
                    addChatMessage('bot', 'I apologize, but I\'m having trouble responding right now. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Chatbot AJAX error:', error);
                hideTypingIndicator();
                addChatMessage('bot', 'I\'m sorry, I\'m experiencing technical difficulties. Please try again in a moment.');
            }
        });
    }
    
    /**
     * Add message to chat interface
     */
    function addChatMessage(sender, message) {
        var $messages = $('.aiqs-chatbot-messages');
        var messageClass = sender === 'user' ? 'aiqs-message-user' : 'aiqs-message-bot';
        
        var html = '<div class="aiqs-message ' + messageClass + '">' + message + '</div>';
        $messages.append(html);
        
        // Scroll to bottom
        $messages.scrollTop($messages[0].scrollHeight);
        
        // Store in local history
        window.aiqs_chatbot_state.message_history.push({
            sender: sender,
            message: message,
            timestamp: Date.now()
        });
    }
    
    /**
     * Show typing indicator
     */
    function showTypingIndicator() {
        window.aiqs_chatbot_state.is_typing = true;
        var $messages = $('.aiqs-chatbot-messages');
        $messages.append('<div class="aiqs-message aiqs-message-bot aiqs-typing-indicator">Rita is typing...</div>');
        $messages.scrollTop($messages[0].scrollHeight);
    }
    
    /**
     * Hide typing indicator
     */
    function hideTypingIndicator() {
        window.aiqs_chatbot_state.is_typing = false;
        $('.aiqs-typing-indicator').remove();
    }
    
    /**
     * Load conversation history
     */
    function loadConversationHistory() {
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_get_conversation_history',
                session_id: window.aiqs_chatbot_state.session_id,
                nonce: aiqs_data.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    if (response.data.recent_messages) {
                        response.data.recent_messages.forEach(function(msg) {
                            if (msg.user_message) {
                                addChatMessage('user', msg.user_message);
                            }
                            if (msg.ai_response) {
                                addChatMessage('bot', msg.ai_response);
                            }
                        });
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('Could not load conversation history:', error);
            }
        });
    }
    
    /**
     * Show recommendations
     */
    function showRecommendations(recommendations) {
        if (!recommendations || recommendations.length === 0) return;
        
        var html = '<div class="aiqs-recommendations">';
        html += '<h4>📚 Study Recommendations for You:</h4>';
        
        recommendations.forEach(function(rec) {
            html += '<div class="aiqs-recommendation-item ' + rec.priority_level + '">';
            html += '<div class="aiqs-recommendation-text">' + rec.recommendation_text + '</div>';
            if (rec.recommendation_type) {
                html += '<div class="aiqs-recommendation-type">Topic: ' + rec.recommendation_type + '</div>';
            }
            html += '</div>';
        });
        
        html += '</div>';
        
        addChatMessage('bot', html);
    }
    
    /**
     * Update student insights (for logged-in users)
     */
    function updateStudentInsights(insights) {
        if (!insights || !aiqs_data.current_user_id) return;
        
        // Update any insights panel on the page
        if ($('.aiqs-student-insights').length > 0) {
            var html = '<h4>Your Learning Insights</h4>';
            
            if (insights.weekly_study_time) {
                html += '<p>This week: ' + insights.weekly_study_time + ' minutes of study time</p>';
            }
            
            if (insights.engagement_level) {
                html += '<p>Engagement level: ' + insights.engagement_level + '</p>';
            }
            
            if (insights.active_days) {
                html += '<p>Active study days: ' + insights.active_days + '</p>';
            }
            
            $('.aiqs-student-insights').html(html);
        }
    }
    
    /**
     * Initialize Performance Interface
     */
    function initializePerformanceInterface() {
        // Load performance data if on performance page
        if ($('.aiqs-performance-container').length > 0) {
            loadPerformanceData();
        }
        
        // Performance exam filter
        $('#aiqs-performance-exam-select').on('change', function() {
            loadPerformanceData($(this).val());
        });
    }
    
    /**
     * Load performance data
     */
    function loadPerformanceData(examId) {
        if (!aiqs_data.current_user_id) {
            showMessage('error', 'Please log in to view performance data');
            return;
        }
        
        $.ajax({
            url: aiqs_data.ajax_url,
            type: 'POST',
            data: {
                action: 'aiqs_get_performance',
                exam_id: examId || null,
                nonce: aiqs_data.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayPerformanceData(response.data);
                } else {
                    showMessage('error', 'No performance data available');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading performance data:', error);
                showMessage('error', 'Error loading performance data');
            }
        });
    }
    
    /**
     * Display performance data
     */
    function displayPerformanceData(data) {
        // Implementation would depend on your performance template structure
        console.log('Performance data loaded:', data);
        
        // Update performance cards, charts, etc.
        if (data.performance && data.performance.length > 0) {
            // Update existing performance display
            // This would integrate with your existing performance template
        }
    }
    
    /**
     * Utility Functions
     */
    
    /**
     * Show message to user
     */
    function showMessage(type, message) {
        // Remove existing messages
        $('.aiqs-message-alert').remove();
        
        var alertClass = 'aiqs-message-alert aiqs-' + type;
        var html = '<div class="' + alertClass + '">' + message + '</div>';
        
        // Add to appropriate container
        if ($('#aiqs-quiz-container').is(':visible')) {
            $('#aiqs-quiz-container').prepend(html);
        } else if ($('#aiqs-subjects-container').length) {
            $('#aiqs-subjects-container').prepend(html);
        } else {
            $('.aiqs-container').first().prepend(html);
        }
        
        // Auto-remove after 5 seconds for non-error messages
        if (type !== 'error') {
            setTimeout(function() {
                $('.' + alertClass).fadeOut();
            }, 5000);
        }
    }
    
    /**
     * Format time in seconds to readable format
     */
    function formatTime(seconds) {
        var hours = Math.floor(seconds / 3600);
        var minutes = Math.floor((seconds % 3600) / 60);
        var secs = seconds % 60;
        
        if (hours > 0) {
            return hours + 'h ' + minutes + 'm ' + secs + 's';
        } else if (minutes > 0) {
            return minutes + 'm ' + secs + 's';
        } else {
            return secs + 's';
        }
    }
    
    /**
     * Debug function
     */
    function debugLog(message, data) {
        if (aiqs_data.debug) {
            console.log('AIQS Debug: ' + message, data);
        }
    }
    
    // Expose some functions globally for external use
    window.aiqs_functions = {
        startQuiz: startQuiz,
        sendChatbotMessage: sendChatbotMessage,
        loadPerformanceData: loadPerformanceData,
        showMessage: showMessage
    };

})(jQuery);
