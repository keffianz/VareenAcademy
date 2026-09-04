/**
 * VEREEN Academy - AI Assistant Widget
 * Floating chat widget for students to ask questions about lessons
 */

class AIAssistantWidget {
    constructor() {
        this.isOpen = false;
        this.lessons = [];
        this.currentLesson = null;
        this.dailyLimit = 40;
        this.remaining = 40;
        this.isLoading = false;
        
        this.init();
    }
    
    init() {
        // Only show for student pages
        const role = document.body.dataset.userRole;
        if (role !== 'student') {
            return;
        }
        
        // Create widget HTML
        this.createWidget();
        this.attachEventListeners();
        
        // Server-enforced assessment lock: hide the assistant during quizzes/exams.
        // The server also rejects 'ask' requests while locked (defense in depth).
        this.refreshLockState();
        
        // Load lessons when opened
        document.addEventListener('aiAssistant:opened', () => this.loadLessons());
    }
    
    async refreshLockState() {
        try {
            const res = await fetch('/lms_vareen/src/api/ai_assistant.php?action=status');
            if (!res.ok) return;
            const data = await res.json();
            if (data && data.success && data.assessment_locked) {
                this.applyAssessmentLock();
            }
        } catch (e) {
            // Widget stays visible; the server still enforces the lock on 'ask'
        }
    }
    
    applyAssessmentLock() {
        const widget = document.getElementById('ai-assistant-widget');
        if (widget) {
            widget.style.display = 'none';
        }
        document.body.classList.add('ai-assessment-locked');
    }
    
    createWidget() {
        const html = `
            <div id="ai-assistant-widget" class="ai-assistant-widget">
                <div class="ai-bubble" id="aiBubble">
                    <i class="fas fa-robot"></i>
                </div>
                
                <div class="ai-panel" id="aiPanel">
                    <div class="ai-header">
                        <h3>Ask My Lesson</h3>
                        <button type="button" class="ai-close" id="aiClose">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="ai-content" id="aiContent">
                        <!-- Step 1: Select Lesson -->
                        <div class="ai-step ai-step-lessons" id="aiStepLessons">
                            <p class="ai-info">Select a lesson to ask about:</p>
                            <div class="ai-lessons-list" id="aiLessonsList">
                                <div class="ai-loading">Loading lessons...</div>
                            </div>
                            <div class="ai-usage">
                                <small id="aiUsageText">Loading usage...</small>
                            </div>
                        </div>
                        
                        <!-- Step 2: Chat -->
                        <div class="ai-step ai-step-chat" id="aiStepChat" style="display:none;">
                            <div class="ai-chat-header">
                                <button type="button" class="ai-back" id="aiBack">
                                    <i class="fas fa-arrow-left"></i>
                                </button>
                                <div>
                                    <h4 id="aiChatTitle">Lesson</h4>
                                    <small id="aiChatCourse">Course</small>
                                </div>
                            </div>
                            
                            <div class="ai-chat-messages" id="aiChatMessages"></div>
                            
                            <div class="ai-chat-input">
                                <textarea 
                                    id="aiQuestion" 
                                    placeholder="Ask a question..." 
                                    rows="3"
                                ></textarea>
                                <button type="button" id="aiSendBtn" class="ai-send-btn">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </div>
                        
                        <!-- Error/Message -->
                        <div class="ai-step ai-step-message" id="aiStepMessage" style="display:none;">
                            <div class="ai-message" id="aiMessage"></div>
                            <button type="button" id="aiMessageAction" class="ai-btn">Back</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
    }
    
    attachEventListeners() {
        const bubble = document.getElementById('aiBubble');
        const close = document.getElementById('aiClose');
        const back = document.getElementById('aiBack');
        const send = document.getElementById('aiSendBtn');
        const messageAction = document.getElementById('aiMessageAction');
        
        bubble.addEventListener('click', () => this.toggle());
        close.addEventListener('click', () => this.close());
        back.addEventListener('click', () => this.goBackToLessons());
        send.addEventListener('click', () => this.askQuestion());
        messageAction.addEventListener('click', () => this.goBackToLessons());
        
        // Allow Enter to send message (Shift+Enter for new line)
        document.getElementById('aiQuestion').addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.askQuestion();
            }
        });
        
        // Lesson selection
        document.addEventListener('click', (e) => {
            if (e.target.closest('.ai-lesson-item')) {
                this.selectLesson(e.target.closest('.ai-lesson-item'));
            }
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        const widget = document.getElementById('ai-assistant-widget');
        widget.classList.add('open');
        this.isOpen = true;
        document.dispatchEvent(new Event('aiAssistant:opened'));
    }
    
    close() {
        const widget = document.getElementById('ai-assistant-widget');
        widget.classList.remove('open');
        this.isOpen = false;
    }
    
    async loadLessons() {
        const list = document.getElementById('aiLessonsList');
        
        try {
            const response = await fetch('/lms_vareen/src/api/ai_assistant.php?action=my_lessons');
            const data = await response.json();
            
            if (!data.success) {
                this.showMessage('Error', data.message || 'Failed to load lessons');
                return;
            }
            
            this.lessons = data.lessons;
            this.dailyLimit = data.daily_limit;
            this.remaining = data.remaining;
            
            this.updateUsageDisplay();
            
            if (this.lessons.length === 0) {
                list.innerHTML = '<div class="ai-empty">No lessons available yet. Enroll in a course first!</div>';
                return;
            }
            
            const courseLessons = {};
            this.lessons.forEach(lesson => {
                if (!courseLessons[lesson.course_id]) {
                    courseLessons[lesson.course_id] = {
                        title: lesson.course_title,
                        lessons: []
                    };
                }
                courseLessons[lesson.course_id].lessons.push(lesson);
            });
            
            let html = '';
            for (const courseId in courseLessons) {
                const course = courseLessons[courseId];
                html += `<div class="ai-course-group">
                    <div class="ai-course-title">${this.escapeHtml(course.title)}</div>`;
                
                course.lessons.forEach(lesson => {
                    html += `<button type="button" class="ai-lesson-item" data-lesson-id="${lesson.id}" data-course="${course.title}" data-lesson="${lesson.title}">
                        <i class="fas fa-book"></i>
                        ${this.escapeHtml(lesson.title)}
                    </button>`;
                });
                
                html += '</div>';
            }
            
            list.innerHTML = html;
        } catch (error) {
            console.error('AI Assistant Error:', error);
            list.innerHTML = '<div class="ai-error">Failed to load lessons. Please try again.</div>';
        }
    }
    
    selectLesson(element) {
        const lessonId = element.dataset.lessonId;
        const lessonTitle = element.dataset.lesson;
        const courseTitle = element.dataset.course;
        
        this.currentLesson = { id: lessonId, title: lessonTitle, course: courseTitle };
        
        document.getElementById('aiChatTitle').textContent = lessonTitle;
        document.getElementById('aiChatCourse').textContent = courseTitle;
        document.getElementById('aiChatMessages').innerHTML = '';
        document.getElementById('aiQuestion').value = '';
        
        // Switch to chat view
        document.getElementById('aiStepLessons').style.display = 'none';
        document.getElementById('aiStepChat').style.display = 'flex';
        document.getElementById('aiStepMessage').style.display = 'none';
        
        // Focus on input
        setTimeout(() => document.getElementById('aiQuestion').focus(), 100);
    }
    
    async askQuestion() {
        if (this.isLoading) return;
        
        const question = document.getElementById('aiQuestion').value.trim();
        if (!question) {
            this.showMessage('Oops', 'Please enter a question');
            return;
        }
        
        if (!this.currentLesson) {
            this.showMessage('Error', 'Please select a lesson first');
            return;
        }
        
        this.isLoading = true;
        const sendBtn = document.getElementById('aiSendBtn');
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        // Add user message to chat
        this.addMessageToChat('You', question, 'user');
        document.getElementById('aiQuestion').value = '';
        
        try {
            const response = await fetch('/lms_vareen/src/api/ai_assistant.php?action=ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                },
                body: new URLSearchParams({
                    'lesson_id': this.currentLesson.id,
                    'question': question
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.addMessageToChat('VAREEN AI', data.answer, 'ai');
                this.remaining = Math.max(0, this.remaining - 1);
                this.updateUsageDisplay();
            } else {
                this.showMessage('Unable to Answer', data.message || 'An error occurred');
            }
        } catch (error) {
            console.error('AI Assistant Error:', error);
            this.showMessage('Error', 'Failed to send question. Please try again.');
        } finally {
            this.isLoading = false;
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
        }
    }
    
    addMessageToChat(sender, text, type) {
        const messages = document.getElementById('aiChatMessages');
        const msgDiv = document.createElement('div');
        msgDiv.className = `ai-message ai-message-${type}`;
        msgDiv.innerHTML = `
            <div class="ai-message-sender">${this.escapeHtml(sender)}</div>
            <div class="ai-message-text">${this.escapeHtml(text).replace(/\n/g, '<br>')}</div>
        `;
        messages.appendChild(msgDiv);
        messages.scrollTop = messages.scrollHeight;
    }
    
    goBackToLessons() {
        this.currentLesson = null;
        document.getElementById('aiStepLessons').style.display = 'block';
        document.getElementById('aiStepChat').style.display = 'none';
        document.getElementById('aiStepMessage').style.display = 'none';
    }
    
    showMessage(title, text) {
        document.getElementById('aiMessage').innerHTML = `
            <h4>${this.escapeHtml(title)}</h4>
            <p>${this.escapeHtml(text)}</p>
        `;
        document.getElementById('aiStepLessons').style.display = 'none';
        document.getElementById('aiStepChat').style.display = 'none';
        document.getElementById('aiStepMessage').style.display = 'flex';
    }
    
    updateUsageDisplay() {
        const usage = document.getElementById('aiUsageText');
        if (usage) {
            usage.textContent = `Questions today: ${this.dailyLimit - this.remaining}/${this.dailyLimit} · Remaining: ${this.remaining}`;
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.aiAssistant = new AIAssistantWidget();
});
