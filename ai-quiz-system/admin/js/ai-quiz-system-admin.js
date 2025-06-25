/**
 * Admin JavaScript for AI Quiz System
 */
(function($) {
    'use strict';

    // Initialize admin functionality
    function initAdmin() {
        // Handle modal dialogs
        $('.aiqs-modal-close').on('click', function() {
            $(this).closest('.aiqs-modal').hide();
        });

        // Close modal when clicking outside the content
        $('.aiqs-modal').on('click', function(e) {
            if ($(e.target).hasClass('aiqs-modal')) {
                $(this).hide();
            }
        });
        
        // Delete question
        $('.delete-question').on('click', function() {
            var questionId = $(this).data('id');
            
            if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aiqs_delete_question',
                        nonce: aiqs_admin_data.nonce,
                        question_id: questionId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || 'Failed to delete question.');
                        }
                    },
                    error: function() {
                        alert('Server error while deleting question.');
                    }
                });
            }
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initAdmin();
    });

})(jQuery);
        // Handle exam select change in Questions page
        $('#exam-select').on('change', function() {
            var examId = $(this).val();
            
            if (examId) {
                // Redirect to the same page with exam_id parameter
                var url = new URL(window.location.href);
                url.searchParams.set('exam_id', examId);
                url.searchParams.delete('subject_id'); // Reset subject when exam changes
                window.location.href = url.toString();
            }
        });
        
        // Generate AI Questions
        $('#generate-ai-questions').on('click', function() {
            var subjectId = $(this).data('subject');
            var button = $(this);
            
            if (confirm('Generate new AI questions for this subject?')) {
                button.prop('disabled', true).text('Generating...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'aiqs_generate_questions',
                        nonce: aiqs_admin_data.nonce,
                        subject_id: subjectId
                    },
                    success: function(response) {
                        button.prop('disabled', false).text('Generate AI Questions');
                        
                        if (response.success) {
                            alert('Questions generated successfully!');
                            location.reload();
                        } else {
                            alert(response.data.message || 'Failed to generate questions.');
                        }
                    },
                    error: function() {
                        button.prop('disabled', false).text('Generate AI Questions');
                        alert('Server error while generating questions.');
                    }
                });
            }
        });
        
        // Add question manually
        $('#add-question').on('click', function() {
            var subjectId = $(this).data('subject');
            
            // Reset form
            $('#edit-question-id').val('');
            $('#edit-subject-id').val(subjectId);
            $('#edit-question').val('');
            $('#edit-option-a').val('');
            $('#edit-option-b').val('');
            $('#edit-option-c').val('');
            $('#edit-option-d').val('');
            $('#edit-correct-answer').val('A');
            $('#edit-explanation').val('');
            $('#edit-difficulty').val('medium');
            $('#edit-source').val('manual');
            
            // Show modal
            $('#question-edit-modal').show();
        });
        
        // View question
        $('.view-question').on('click', function() {
            var questionId = $(this).data('id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiqs_get_question',
                    nonce: aiqs_admin_data.nonce,
                    question_id: questionId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var question = response.data;
                        var html = '<div class="aiqs-question-details">';
                        html += '<p><strong>Question:</strong> ' + question.question + '</p>';
                        html += '<p><strong>Option A:</strong> ' + question.option_a + '</p>';
                        html += '<p><strong>Option B:</strong> ' + question.option_b + '</p>';
                        html += '<p><strong>Option C:</strong> ' + question.option_c + '</p>';
                        html += '<p><strong>Option D:</strong> ' + question.option_d + '</p>';
                        html += '<p><strong>Correct Answer:</strong> ' + question.correct_answer + '</p>';
                        
                        if (question.explanation) {
                            html += '<p><strong>Explanation:</strong> ' + question.explanation + '</p>';
                        }
                        
                        html += '<p><strong>Difficulty:</strong> ' + question.difficulty + '</p>';
                        html += '<p><strong>Source:</strong> ' + question.source + '</p>';
                        html += '</div>';
                        
                        $('#question-view-content').html(html);
                        $('#question-view-modal').show();
                    } else {
                        alert('Failed to load question details.');
                    }
                },
                error: function() {
                    alert('Server error while loading question details.');
                }
            });
        });
        
        // Edit question
        $('.edit-question').on('click', function() {
            var questionId = $(this).data('id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiqs_get_question',
                    nonce: aiqs_admin_data.nonce,
                    question_id: questionId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var question = response.data;
                        
                        // Fill the form
                        $('#edit-question-id').val(question.id);
                        $('#edit-subject-id').val(question.subject_id);
                        $('#edit-question').val(question.question);
                        $('#edit-option-a').val(question.option_a);
                        $('#edit-option-b').val(question.option_b);
                        $('#edit-option-c').val(question.option_c);
                        $('#edit-option-d').val(question.option_d);
                        $('#edit-correct-answer').val(question.correct_answer);
                        $('#edit-explanation').val(question.explanation);
                        $('#edit-difficulty').val(question.difficulty);
                        $('#edit-source').val(question.source);
                        
                        // Show modal
                        $('#question-edit-modal').show();
                    } else {
                        alert('Failed to load question data.');
                    }
                },
                error: function() {
                    alert('Server error while loading question data.');
                }
            });
        });
        
        // Save question
        $('#edit-question-form').on('submit', function(e) {
            e.preventDefault();
            
            var questionId = $('#edit-question-id').val();
            var formData = $(this).serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aiqs_save_question',
                    nonce: aiqs_admin_data.nonce,
                    ...formData
                },
                success: function(response) {
                    if (response.success) {
                        $('#question-edit-modal').hide();
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to save question.');
                    }
                },
                error: function() {
                    alert('Server error while saving question.');
                }
            });
        });