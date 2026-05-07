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

<style scoped>
.admin-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 32px 24px;
}

h1 {
    font-size: 26px;
    color: #1F4E79;
    margin-bottom: 24px;
}

.tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
}

.tabs button {
    padding: 10px 20px;
    border: 2px solid #2E75B6;
    border-radius: 4px;
    background: white;
    color: #2E75B6;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.tabs button.active {
    background: #2E75B6;
    color: white;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.card h2 {
    font-size: 18px;
    color: #1F4E79;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 12px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #CCC;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group textarea {
    height: 80px;
    resize: vertical;
}

.btn-add {
    background: #1F4E79;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-add:hover:not(:disabled) {
    background: #163D5F;
}

.btn-add:disabled {
    background: #999;
    cursor: not-allowed;
}

.error-text {
    color: #C00000;
    font-size: 13px;
    margin-left: 12px;
}

.empty {
    text-align: center;
    color: #888;
    padding: 20px;
}

.course-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border: 1px solid #E0E0E0;
    border-radius: 6px;
    margin-bottom: 10px;
}

.course-info h3 {
    font-size: 15px;
    color: #333;
    margin-bottom: 4px;
}

.course-info p {
    font-size: 12px;
    color: #888;
    margin-bottom: 6px;
}

.badge {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 999px;
    font-weight: 500;
}

.badge.published {
    background: #D4EDDA;
    color: #155724;
}

.badge.draft {
    background: #FFF3CD;
    color: #856404;
}

.course-actions {
    display: flex;
    gap: 8px;
}

.btn-publish {
    background: #2E75B6;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-publish:hover {
    background: #1F4E79;
}

.btn-delete {
    background: #C00000;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-delete:hover {
    background: #9B0000;
}
</style>