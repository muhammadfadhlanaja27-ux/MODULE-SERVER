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
                    <progress :value="progressPercent" max="100"></progress>
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