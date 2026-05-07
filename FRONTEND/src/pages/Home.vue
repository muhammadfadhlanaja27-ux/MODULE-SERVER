<template>
    <div class="home-container">
        <!-- Loading -->
        <div class="loading" v-if="loading">
            <p>Loading...</p>
        </div>

        <div v-else>
            <!-- My Courses Section -->
            <section class="section">
                <h2>My Courses</h2>
                <div v-if="myCourses.length === 0" class="empty-state">
                    <p>Kamu belum terdaftar di course apapun.</p>
                </div>
                <div class="course-grid" v-else>
                    <div
                        class="course-card my-course"
                        v-for="item in myCourses"
                        :key="item.course.id"
                        @click="goToCourse(item.course.slug)"
                    >
                        <h3>{{ item.course.name }}</h3>
                        <p>{{ item.course.description }}</p>
                        <progress :value="getProgress(item)" max="100"></progress>
                        <span class="progress-text">
                            {{ getProgress(item) }}% selesai
                        </span>
                    </div>
                </div>
            </section>

            <!-- Available Courses Section -->
            <section class="section">
                <h2>Available Courses</h2>
                <div v-if="availableCourses.length === 0" class="empty-state">
                    <p>Tidak ada course tersedia saat ini.</p>
                </div>
                <div class="course-grid" v-else>
                    <div
                        class="course-card"
                        v-for="course in availableCourses"
                        :key="course.id"
                        @click="goToCourse(course.slug)"
                    >
                        <h3>{{ course.name }}</h3>
                        <p>{{ course.description }}</p>
                        <span class="badge-available">Tersedia</span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script>
import api from '../services/api'

export default {
    name: 'Home',
    data() {
        return {
            allCourses: [],
            progress: [],
            courseDetails: {},
            loading: true
        }
    },
    computed: {
        myCourses() {
            return this.progress
        },
        availableCourses() {
            const enrolledIds = this.progress.map(p => p.course.id)
            return this.allCourses.filter(c => !enrolledIds.includes(c.id))
        }
    },
    methods: {
        async fetchData() {
            this.loading = true
            try {
                const [coursesRes, progressRes] = await Promise.all([
                    api.get('/courses'),
                    api.get('/users/progress')
                ])

                this.allCourses = coursesRes.data.data.courses
                this.progress  = progressRes.data.data.progress

                // Fetch detail setiap course yang diikuti
                // untuk dapat total lessons
                for (const item of this.progress) {
                    const detailRes = await api.get(`/courses/${item.course.slug}`)
                    const course = detailRes.data.data
                    const totalLessons = course.sets.reduce((total, set) => {
                        return total + set.lessons.length
                    }, 0)
                    this.courseDetails[item.course.id] = totalLessons
                }

            } catch (error) {
                console.error(error)
            } finally {
                this.loading = false
            }
        },

        getProgress(item) {
            const totalLessons = this.courseDetails[item.course.id] || 0
            const completedLessons = item.completed_lessons?.length || 0
            if (totalLessons === 0) return 0
            return Math.round((completedLessons / totalLessons) * 100)
        },

        goToCourse(slug) {
            this.$router.push(`/${slug}`)
        }
    },
    mounted() {
        this.fetchData()
    }
}
</script>