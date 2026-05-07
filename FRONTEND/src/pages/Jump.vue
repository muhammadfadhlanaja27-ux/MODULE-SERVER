<template>
    <div class="jump-container">
        <!-- Loading -->
        <div class="loading" v-if="loading">
            <p>Loading...</p>
        </div>

        <div v-else-if="currentSet">
            <!-- Header -->
            <div class="jump-header">
                <button class="btn-back" @click="$router.push(`/${$route.params.course_slug}`)">
                    ← Kembali
                </button>
                <h1>{{ currentSet.name }}</h1>

                <!-- Progress -->
                <div class="progress-section">
                    <div class="progress-info">
                        <span>Progress Set</span>
                        <span>{{ completedInSet }} / {{ totalInSet }} lesson</span>
                    </div>
                    <progress :value="progressPercent" max="100"></progress>
                </div>
            </div>

            <!-- Content -->
            <div class="content-card" v-if="currentContent">

                <!-- Lesson Info -->
                <div class="lesson-info">
                    <span class="lesson-label">📘 {{ currentLesson?.name }}</span>
                </div>

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

            <!-- Semua selesai -->
            <div class="all-done" v-else>
                <h2>🎉 Semua lesson di set ini selesai!</h2>
                <p>Kamu telah menyelesaikan semua lesson di <strong>{{ currentSet.name }}</strong></p>
                <button class="btn-continue" @click="$router.push(`/${$route.params.course_slug}`)">
                    Kembali ke Course
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import api from '../services/api'

export default {
    name: 'Jump',
    data() {
        return {
            course: null,
            currentSet: null,
            allLessons: [],
            currentLessonIndex: 0,
            currentContentIndex: 0,
            completedLessonIds: [],
            answerChecked: false,
            isCorrect: false,
            selectedOptionId: null,
            loading: true
        }
    },
    computed: {
        // Lesson yang sedang dikerjakan
        currentLesson() {
            return this.allLessons[this.currentLessonIndex] || null
        },

        // Content yang sedang ditampilkan
        currentContent() {
            if (!this.currentLesson) return null
            return this.currentLesson.contents[this.currentContentIndex] || null
        },

        // Total lesson di set ini
        totalInSet() {
            return this.allLessons.length
        },

        // Lesson yang sudah selesai di set ini
        completedInSet() {
            return this.allLessons.filter(l =>
                this.completedLessonIds.includes(l.id)
            ).length
        },

        // Persentase progress set
        progressPercent() {
            if (this.totalInSet === 0) return 0
            return Math.round((this.completedInSet / this.totalInSet) * 100)
        }
    },
    methods: {
        async fetchData() {
            this.loading = true
            try {
                const slug = this.$route.params.course_slug
                const lessonId = this.$route.params.lesson_id

                // Ambil detail course
                const courseRes = await api.get(`/courses/${slug}`)
                this.course = courseRes.data.data

                // Cari set yang berisi lesson ini
                let foundSet = null
                for (const set of this.course.sets) {
                    const found = set.lessons.find(l => l.id == lessonId)
                    if (found) {
                        foundSet = set
                        break
                    }
                }

                this.currentSet = foundSet
                this.allLessons = foundSet?.lessons || []

                // Ambil progress user
                const progressRes = await api.get('/users/progress')
                const progress = progressRes.data.data.progress
                const enrolled = progress.find(p => p.course.slug === slug)

                if (enrolled) {
                    this.completedLessonIds = enrolled.completed_lessons.map(l => l.id)
                }

                // Mulai dari lesson yang belum selesai
                this.currentLessonIndex = this.allLessons.findIndex(
                    l => !this.completedLessonIds.includes(l.id)
                )

                if (this.currentLessonIndex === -1) {
                    this.currentLessonIndex = this.allLessons.length
                }

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
                const lessonId = this.currentLesson.id
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
            const lesson = this.currentLesson

            // Masih ada content berikutnya di lesson ini
            if (this.currentContentIndex < lesson.contents.length - 1) {
                this.currentContentIndex++
                this.resetAnswer()
                return
            }

            // Semua content lesson ini selesai, complete lesson
            await this.completeCurrentLesson()
        },

        async completeCurrentLesson() {
            try {
                const lessonId = this.currentLesson.id
                await api.put(`/lessons/${lessonId}/complete`)

                // Tambahkan ke completedLessonIds
                this.completedLessonIds.push(lessonId)

                // Pindah ke lesson berikutnya
                if (this.currentLessonIndex < this.allLessons.length - 1) {
                    this.currentLessonIndex++
                    this.currentContentIndex = 0
                    this.resetAnswer()
                } else {
                    // Semua lesson di set ini selesai
                    this.currentLessonIndex = this.allLessons.length
                }

            } catch (error) {
                console.error(error)
            }
        },

        resetAnswer() {
            this.answerChecked = false
            this.isCorrect = false
            this.selectedOptionId = null
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
        this.fetchData()
    }
}
</script>