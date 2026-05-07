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
                        <div class="progress-bar">
                            <div
                                class="progress-fill"
                                :style="{ width: getProgress(item) + '%' }"
                            ></div>
                        </div>
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

<style scoped>
.home-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 32px 24px;
}

.loading {
    text-align: center;
    padding: 60px;
    color: #666;
}

.section {
    margin-bottom: 48px;
}

.section h2 {
    font-size: 22px;
    color: #1F4E79;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #BDD7EE;
}

.empty-state {
    background: #F5F5F5;
    padding: 24px;
    border-radius: 8px;
    text-align: center;
    color: #888;
}

.course-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.course-card {
    background: white;
    border: 1px solid #E0E0E0;
    border-radius: 8px;
    padding: 20px;
    cursor: pointer;
    transition: box-shadow 0.2s, transform 0.2s;
}

.course-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.course-card.my-course {
    border-left: 4px solid #2E75B6;
}

.course-card h3 {
    font-size: 16px;
    color: #1F4E79;
    margin-bottom: 8px;
}

.course-card p {
    font-size: 13px;
    color: #666;
    margin-bottom: 12px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.progress-bar {
    background: #E0E0E0;
    border-radius: 999px;
    height: 6px;
    margin-bottom: 6px;
    overflow: hidden;
}

.progress-fill {
    background: #2E75B6;
    height: 100%;
    border-radius: 999px;
    transition: width 0.4s ease;
}

.progress-text {
    font-size: 12px;
    color: #2E75B6;
    font-weight: 500;
}

.badge-available {
    display: inline-block;
    background: #E8F4FD;
    color: #2E75B6;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
}
</style>