<template>
    <div class="lesson-container">
        <!-- Loading -->
        <div class="loading" v-if="loading">
            <p>Loading...</p>
        </div>

        <div v-else-if="lesson">
            <!-- Header -->
            <div class="lesson-header">
                <button class="btn-back" @click="$router.push(`/${$route.params.course_slug}`)">
                    ← Kembali
                </button>
                <h1>{{ lesson.name }}</h1>

                <!-- Progress -->
                <div class="progress-section">
                    <div class="progress-info">
                        <span>Progress</span>
                        <span>{{ currentIndex + 1 }} / {{ lesson.contents.length }}</span>
                    </div>
                    <div class="progress-bar">
                        <div
                            class="progress-fill"
                            :style="{ width: progressPercent + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="content-card" v-if="currentContent">

                <!-- Type: Learn -->
                <div v-if="currentContent.type === 'learn'">
                    <div class="content-badge learn">📖 Materi</div>
                    <div class="content-text">{{ currentContent.content }}</div>
                    <button class="btn-continue" @click="nextContent">
                        Continue →
                    </button>
                </div>

                <!-- Type: Quiz -->
                <div v-else-if="currentContent.type === 'quiz'">
                    <div class="content-badge quiz">❓ Quiz</div>
                    <div class="content-text">{{ currentContent.content }}</div>

                    <!-- Options -->
                    <div class="options-list">
                        <button
                            class="option-btn"
                            v-for="option in currentContent.options"
                            :key="option.id"
                            :class="getOptionClass(option.id)"
                            @click="checkAnswer(option.id)"
                            :disabled="answerChecked"
                        >
                            {{ option.option_text }}
                        </button>
                    </div>

                    <!-- Feedback -->
                    <div class="feedback correct" v-if="answerChecked && isCorrect">
                        ✅ Jawaban benar! Lanjutkan.
                        <button class="btn-continue" @click="nextContent">
                            Continue →
                        </button>
                    </div>
                    <div class="feedback wrong" v-if="answerChecked && !isCorrect">
                        ❌ Jawaban salah! Coba lagi.
                        <button class="btn-retry" @click="retryAnswer">
                            Coba Lagi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import api from '../services/api'

export default {
    name: 'Lesson',
    data() {
        return {
            lesson: null,
            currentIndex: 0,
            answerChecked: false,
            isCorrect: false,
            selectedOptionId: null,
            loading: true
        }
    },
    computed: {
        currentContent() {
            if (!this.lesson) return null
            return this.lesson.contents[this.currentIndex]
        },
        progressPercent() {
            if (!this.lesson) return 0
            return Math.round(((this.currentIndex + 1) / this.lesson.contents.length) * 100)
        }
    },
    methods: {
        async fetchLesson() {
            this.loading = true
            try {
                const lessonId = this.$route.params.lesson_id
                const slug = this.$route.params.course_slug

                // Ambil detail course untuk dapat lesson
                const courseRes = await api.get(`/courses/${slug}`)
                const course = courseRes.data.data

                // Cari lesson berdasarkan id
                let foundLesson = null
                for (const set of course.sets) {
                    const lesson = set.lessons.find(l => l.id == lessonId)
                    if (lesson) {
                        foundLesson = lesson
                        break
                    }
                }

                this.lesson = foundLesson

            } catch (error) {
                console.error(error)
            } finally {
                this.loading = false
            }
        },

        async checkAnswer(optionId) {
            if (this.answerChecked) return

            this.selectedOptionId = optionId
            this.answerChecked = true

            try {
                const lessonId = this.$route.params.lesson_id
                const contentId = this.currentContent.id

                const response = await api.post(
                    `/lessons/${lessonId}/contents/${contentId}/check`,
                    { option_id: optionId }
                )

                this.isCorrect = response.data.data.is_correct

            } catch (error) {
                console.error(error)
            }
        },

        retryAnswer() {
            this.answerChecked = false
            this.isCorrect = false
            this.selectedOptionId = null
        },

        async nextContent() {
            // Kalau masih ada content berikutnya
            if (this.currentIndex < this.lesson.contents.length - 1) {
                this.currentIndex++
                this.answerChecked = false
                this.isCorrect = false
                this.selectedOptionId = null
            } else {
                // Semua content selesai, tandai lesson complete
                await this.completeLesson()
            }
        },

        async completeLesson() {
            try {
                const lessonId = this.$route.params.lesson_id
                await api.put(`/lessons/${lessonId}/complete`)

                // Redirect ke course page
                const slug = this.$route.params.course_slug
                this.$router.push(`/${slug}`)

            } catch (error) {
                console.error(error)
            }
        },

        getOptionClass(optionId) {
            if (!this.answerChecked) return ''
            if (optionId === this.selectedOptionId) {
                return this.isCorrect ? 'correct' : 'wrong'
            }
            return ''
        }
    },
    mounted() {
        this.fetchLesson()
    }
}
</script>

<style scoped>
.lesson-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 32px 24px;
}

.loading {
    text-align: center;
    padding: 60px;
    color: #666;
}

.lesson-header {
    margin-bottom: 32px;
}

.btn-back {
    background: none;
    border: none;
    color: #2E75B6;
    font-size: 14px;
    cursor: pointer;
    padding: 0;
    margin-bottom: 12px;
}

.btn-back:hover {
    text-decoration: underline;
}

.lesson-header h1 {
    font-size: 24px;
    color: #1F4E79;
    margin-bottom: 16px;
}

.progress-section {
    margin-top: 12px;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #666;
    margin-bottom: 6px;
}

.progress-bar {
    background: #E0E0E0;
    border-radius: 999px;
    height: 6px;
    overflow: hidden;
}

.progress-fill {
    background: #2E75B6;
    height: 100%;
    border-radius: 999px;
    transition: width 0.4s ease;
}

.content-card {
    background: white;
    border-radius: 8px;
    padding: 32px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.content-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 16px;
}

.content-badge.learn {
    background: #E8F4FD;
    color: #2E75B6;
}

.content-badge.quiz {
    background: #FFF3CD;
    color: #856404;
}

.content-text {
    font-size: 16px;
    color: #333;
    line-height: 1.7;
    margin-bottom: 24px;
}

.btn-continue {
    background-color: #2E75B6;
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 4px;
    font-size: 15px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-continue:hover {
    background-color: #1F4E79;
}

/* Quiz Options */
.options-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.option-btn {
    text-align: left;
    padding: 14px 18px;
    border: 2px solid #E0E0E0;
    border-radius: 6px;
    background: white;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    color: #333;
}

.option-btn:hover:not(:disabled) {
    border-color: #2E75B6;
    background: #E8F4FD;
}

.option-btn:disabled {
    cursor: not-allowed;
}

.option-btn.correct {
    border-color: #28A745;
    background: #D4EDDA;
    color: #155724;
}

.option-btn.wrong {
    border-color: #DC3545;
    background: #F8D7DA;
    color: #721C24;
}

/* Feedback */
.feedback {
    padding: 14px 18px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.feedback.correct {
    background: #D4EDDA;
    color: #155724;
}

.feedback.wrong {
    background: #F8D7DA;
    color: #721C24;
}

.btn-retry {
    background-color: #DC3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}

.btn-retry:hover {
    background-color: #C82333;
}
</style>