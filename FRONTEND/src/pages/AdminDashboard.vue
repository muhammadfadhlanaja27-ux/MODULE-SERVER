<template>
    <div class="admin-container">
        <h1>Admin Dashboard</h1>

        <!-- Tabs -->
        <div class="tabs">
            <button
                :class="{ active: activeTab === 'courses' }"
                @click="activeTab = 'courses'"
            >
                📚 Courses
            </button>
        </div>

        <!-- Loading -->
        <div class="loading" v-if="loading">Loading...</div>

        <!-- Courses Tab -->
        <div v-else-if="activeTab === 'courses'">

            <!-- Add Course Form -->
            <div class="card">
                <h2>Tambah Course Baru</h2>
                <div class="form-group">
                    <input v-model="newCourse.name" placeholder="Nama Course" />
                </div>
                <div class="form-group">
                    <input v-model="newCourse.slug" placeholder="Slug (contoh: web-dev)" />
                </div>
                <div class="form-group">
                    <textarea v-model="newCourse.description" placeholder="Deskripsi"></textarea>
                </div>
                <button class="btn-add" @click="addCourse" :disabled="adding">
                    {{ adding ? 'Loading...' : '+ Tambah Course' }}
                </button>
                <span class="error-text" v-if="courseError">{{ courseError }}</span>
            </div>

            <!-- Course List -->
            <div class="card">
                <h2>Daftar Course</h2>
                <div class="empty" v-if="courses.length === 0">
                    Belum ada course.
                </div>
                <div class="course-item" v-for="course in courses" :key="course.id">
                    <div class="course-info">
                        <h3>{{ course.name }}</h3>
                        <p>{{ course.slug }}</p>
                        <span class="badge" :class="course.is_published ? 'published' : 'draft'">
                            {{ course.is_published ? 'Published' : 'Draft' }}
                        </span>
                    </div>
                    <div class="course-actions">
                        <button
                            class="btn-publish"
                            @click="togglePublish(course)"
                        >
                            {{ course.is_published ? 'Unpublish' : 'Publish' }}
                        </button>
                        <button class="btn-delete" @click="deleteCourse(course.slug)">
                            Hapus
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
    name: 'AdminDashboard',
    data() {
        return {
            activeTab: 'courses',
            courses: [],
            loading: true,
            adding: false,
            courseError: '',
            newCourse: {
                name: '',
                slug: '',
                description: ''
            }
        }
    },
    methods: {
        async fetchCourses() {
            this.loading = true
            try {
                // Admin bisa lihat semua course termasuk draft
                const res = await api.get('admin/courses')
                this.courses = res.data.data.courses
            } catch (e) {
                console.error(e)
            } finally {
                this.loading = false
            }
        },

        async addCourse() {
            this.courseError = ''
            this.adding = true
            try {
                await api.post('/courses', this.newCourse)
                this.newCourse = { name: '', slug: '', description: '' }
                await this.fetchCourses()
            } catch (e) {
                if (e.response?.data?.errors) {
                    const errors = e.response.data.errors
                    this.courseError = Object.values(errors).flat().join(', ')
                } else {
                    this.courseError = e.response?.data?.message || 'Terjadi kesalahan'
                }
            } finally {
                this.adding = false
            }
        },

        async togglePublish(course) {
            try {
                await api.put(`/courses/${course.slug}`, {
                    name: course.name,
                    description: course.description,
                    is_published: !course.is_published
                })
                await this.fetchCourses()
            } catch (e) {
                console.error(e)
            }
        },

        async deleteCourse(slug) {
            if (!confirm('Yakin ingin menghapus course ini?')) return
            try {
                await api.delete(`/courses/${slug}`)
                await this.fetchCourses()
            } catch (e) {
                console.error(e)
            }
        }
    },
    mounted() {
        this.fetchCourses()
    }
}
</script>