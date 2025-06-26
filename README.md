# AI Quiz System - Complete Setup & Development Guide

## 🎯 **Project Overview**
WordPress plugin for Nigerian/African students with AI-powered quiz generation, admin-controlled exams, and personalized learning assistance.

## 📁 **Current Plugin Structure**
```
ai-quiz-system/
├── ai-quiz-system.php (main plugin file)
├── includes/
│   ├── class-ai-quiz-system.php (core class)
│   ├── class-ai-quiz-system-db.php (database operations)
│   ├── class-ai-quiz-system-engine.php (AI integration)
│   ├── class-ai-quiz-system-activator.php
│   ├── class-ai-quiz-system-deactivator.php
│   ├── class-ai-quiz-system-loader.php
│   └── class-ai-quiz-system-i18n.php
├── admin/
│   ├── class-ai-quiz-system-admin.php (admin interface)
│   ├── partials/
│   │   ├── dashboard.php
│   │   ├── exams.php
│   │   ├── subjects.php (NEW - needs creation)
│   │   ├── questions.php
│   │   ├── settings.php
│   │   └── stats.php
│   ├── css/
│   │   └── ai-quiz-system-admin.css
│   └── js/
│       └── ai-quiz-system-admin.js
├── public/
│   ├── class-ai-quiz-system-public.php (frontend)
│   ├── partials/
│   │   ├── quiz.php
│   │   ├── performance.php
│   │   └── chatbot.php
│   ├── css/
│   │   └── ai-quiz-system-public.css
│   └── js/
│       └── ai-quiz-system-public.js
└── languages/
```

## 🎯 **Current Issues to Fix**
1. **Missing "Manage Subjects" interface** - No dedicated page for subject management
2. **Frontend question loading errors** - AJAX calls failing
3. **Database methods missing** - Several DB methods not implemented
4. **Subject-exam relationship** - Not properly linked

## 💾 **Database Schema**
```sql
-- Exams table
wp_aiqs_exams (
    id, name, description, subject_count, 
    question_count_per_subject, time_limit, 
    passing_score, status, created_at, updated_at
)

-- Subjects table
wp_aiqs_subjects (
    id, name, description, exam_id, 
    status, created_at, updated_at
)

-- Questions table
wp_aiqs_questions (
    id, subject_id, question, option_a, option_b, 
    option_c, option_d, correct_answer, explanation, 
    difficulty, source, image_url, created_at, updated_at
)

-- Quiz attempts
wp_aiqs_quiz_attempts (
    id, user_id, exam_id, session_id, start_time, 
    end_time, score, total_questions, correct_answers, 
    status, ip_address, created_at
)

-- NEW: Enhanced Chatbot Tables
wp_aiqs_chatbot_conversations (
    id, user_id, session_id, conversation_title,
    started_at, last_message_at, message_count,
    conversation_summary, learning_context, status
)

wp_aiqs_chatbot_messages (
    id, conversation_id, user_id, message_type,
    user_message, ai_response, context_data,
    performance_data_used, timestamp, tokens_used
)

wp_aiqs_student_profiles (
    id, user_id, learning_style, strong_subjects,
    weak_subjects, preferred_difficulty, study_goals,
    last_activity, total_study_time, created_at, updated_at
)

wp_aiqs_study_recommendations (
    id, user_id, recommendation_type, subject_id,
    recommendation_text, priority_level, status,
    generated_date, completed_date, effectiveness_score
)

wp_aiqs_learning_analytics (
    id, user_id, session_date, activity_type,
    subject_focus, time_spent, questions_answered,
    accuracy_rate, conversation_quality, insights
)
```

## 🔧 **Development Standards**
- **PHP:** WordPress coding standards
- **JavaScript:** jQuery for AJAX
- **CSS:** BEM methodology
- **Security:** Nonces, sanitization, capability checks
- **Internationalization:** All strings wrapped in __() functions

## 🎯 **Core Features Needed**
1. **Admin Interface:**
   - Dashboard with quick actions
   - Exam management (create, edit, delete)
   - Subject management (NEW - main issue)
   - Question management (AI generation, manual entry, CSV import)
   - Settings (AI configuration)
   - Statistics
   - **Student conversation analytics dashboard**

2. **Frontend Interface:**
   - Quiz selection (exam → subjects → start)
   - Quiz interface with timer
   - Results display
   - Performance tracking
   - **Persistent conversational AI chatbot ("Rita")**

3. **AI Integration:**
   - OpenAI & Claude API support
   - Question generation by subject
   - Personalized feedback
   - Fallback to demo questions
   - **Conversational memory system**
   - **Personalized study recommendations**
   - **Context-aware learning assistance**

4. **NEW: Advanced Chatbot Features:**
   - **Persistent Memory:** Remember all conversations per student
   - **Performance Tracking:** Link quiz results to personalized advice
   - **Study Recommendations:** AI-generated study plans based on weaknesses
   - **Conversation Context:** Maintain ongoing educational relationships
   - **Learning Analytics:** Track learning patterns and preferences
   - **Adaptive Responses:** Adjust teaching style per student

## 🚨 **Priority Fixes Needed**
1. Create `admin/partials/subjects.php`
2. Add subject management methods to admin class
3. Fix frontend AJAX handlers
4. Add missing database methods
5. Update core class hooks

---

# 🖥️ **Cursor IDE Setup Guide**

## Step 1: Installation
1. **Download Cursor:** https://cursor.sh/
2. **Install** like any regular application
3. **Sign up** for Cursor account (free tier available)

## Step 2: Initial Setup
1. **Open Cursor**
2. **File → Open Folder** → Select your plugin directory
3. **Install WordPress extension** (search "WordPress" in extensions)
4. **Enable Copilot** in Cursor settings

## Step 3: Claude Integration
1. **Open Command Palette** (Ctrl/Cmd + Shift + P)
2. **Type "Cursor: Chat"** to open AI chat panel
3. **Configure AI model** to use Claude (in settings)
4. **Set context** to include entire project folder

## Step 4: WordPress Development Setup
1. **Install PHP extension** for syntax highlighting
2. **Set up file associations** for .php files
3. **Configure WordPress snippets**
4. **Enable auto-formatting** for PHP

## Step 5: Project-Specific Configuration
Create `.cursor/settings.json` in your plugin root:
```json
{
  "ai.enabled": true,
  "ai.model": "claude",
  "files.associations": {
    "*.php": "php"
  },
  "workspace.context": "full-project"
}
```

## Step 6: Working with Claude in Cursor
**Essential Commands:**
- `@codebase` - Reference entire project
- `@file filename.php` - Reference specific file
- `@folder admin/` - Reference folder contents

**Example Chat Messages:**
```
"@codebase I need to add a subject management page. 
Current issue: Missing admin/partials/subjects.php and 
related AJAX handlers in admin class."

"@file admin/class-ai-quiz-system-admin.php 
Add methods for subject CRUD operations"

"@folder public/ Fix the frontend JavaScript 
to properly load exam subjects via AJAX"
```

---

# 📋 **Enhanced Template for New Claude Chat**

```markdown
# AI Quiz System WordPress Plugin Development

## Project Context
I'm developing an AI-powered quiz system for Nigerian/African students with:
- Exam and subject management
- AI question generation 
- **ADVANCED CONVERSATIONAL AI CHATBOT ("Rita")**
- **Persistent student memory and personalized learning**

## Key Innovation: Conversational Learning Assistant
The chatbot should:
- **Remember every conversation** with each student
- **Track quiz performance** and link to conversations
- **Generate personalized study recommendations**
- **Maintain learning context** across sessions
- **Adapt teaching style** based on student needs
- **Provide bespoke educational guidance**

## Current Status
- Plugin structure exists but has critical issues
- Missing subject management interface
- Frontend question loading broken  
- Database methods incomplete
- **Chatbot needs conversational memory system**

## Priority Features Needed
1. **Fix basic functionality** (subject management, quiz loading)
2. **Implement persistent chatbot conversations**
3. **Create student learning profiles**
4. **Build personalized recommendation engine**
5. **Add conversation analytics for admins**

## Technical Requirements
- **Conversation Memory:** Store all chat history per student
- **Performance Integration:** Link quiz results to chat recommendations
- **Context Awareness:** Maintain educational relationship continuity
- **Learning Analytics:** Track patterns and provide insights
- **Adaptive AI:** Adjust responses based on student profile

## Immediate Task
1. Fix current plugin issues (subject management, quiz loading)
2. **Design conversational AI architecture**
3. **Implement persistent memory system**
4. **Create personalized learning assistant**

## Project Knowledge
Please reference uploaded project files:
- Plugin ZIP file (latest version)
- Setup guide and documentation
- Database schema (including new chatbot tables)
- File structure map

## Request
Create a fully functional plugin with:
1. Working admin interface and quiz system
2. **Advanced conversational AI chatbot with persistent memory**
3. **Personalized study recommendations based on performance**
4. **Student learning profiles and analytics**

The chatbot should feel like a personal tutor who knows each student's history, strengths, weaknesses, and learning journey.
```

---

# 🎯 **Next Steps Action Plan**

## Phase 1: Foundation + Conversational AI (New Chat Session)
1. **Upload current plugin ZIP to Claude Projects**
2. **Upload this enhanced documentation to Projects**
3. **Ask Claude to fix critical issues AND design conversational AI system**
4. **Get working subject management + persistent chatbot interface**

## Phase 2: Advanced Chatbot Development
1. **Implement conversation memory system**
2. **Create student learning profiles**
3. **Build personalized recommendation engine**
4. **Add conversation analytics dashboard**

## Phase 3: Cursor IDE Setup & Testing
1. **Download and install Cursor**
2. **Test conversational AI with real students**
3. **Refine personalization algorithms**
4. **Optimize performance and memory usage**

## Phase 4: Advanced Learning Features
1. **Adaptive questioning based on chat insights**
2. **Study plan generation and tracking**
3. **Parent/teacher progress reports**
4. **Advanced learning analytics and predictions**

---

# 🔄 **Troubleshooting Guide**

## Common Issues & Solutions

### Plugin Activation Errors
- Check PHP error logs
- Verify file permissions
- Ensure all required files exist
- Check for syntax errors

### AJAX Not Working
- Verify nonce generation
- Check AJAX action hooks
- Ensure proper data sanitization
- Debug with browser console

### Database Issues
- Check table creation on activation
- Verify WordPress database connection
- Ensure proper SQL queries
- Check user permissions

### Frontend Not Loading
- Verify script enqueuing
- Check for JavaScript errors
- Ensure proper AJAX endpoints
- Validate HTML structure

---

# 📝 **Development Checklist**

## Before Each Development Session
- [ ] Backup current plugin
- [ ] Test on staging site first
- [ ] Check PHP error logs
- [ ] Verify WordPress compatibility

## After Each Development Session
- [ ] Test all modified features
- [ ] Check for PHP errors
- [ ] Validate AJAX functionality
- [ ] Update documentation
- [ ] Commit changes (if using version control)

## Before Going Live
- [ ] Full feature testing
- [ ] Database backup
- [ ] Performance testing
- [ ] Security review
- [ ] Mobile compatibility check

---

This documentation package provides everything needed for successful continuation in new chat sessions and professional development setup with Cursor IDE.
