<template>
  <div class="course-container">
    <!-- Loading -->
    <div class="loading" v-if="loading">
      <p>Loading...</p>
    </div>

    <div v-else-if="course">
      <!-- Belum terdaftar -->
      <div v-if="!isEnrolled">
        <div class="course-header">
          <h1>{{ course.name }}</h1>
          <p class="description">{{ course.description }}</p>
          <button
            class="btn-register"
            @click="registerCourse"
            :disabled="registering"
          >
            {{ registering ? "Loading..." : "Register to this course" }}
          </button>
        </div>

        <!-- Outline Sets -->
        <div class="outline">
          <h2>Course Outline</h2>
          <div class="set-list">
            <div class="set-item" v-for="set in course.sets" :key="set.id">
              <span class="set-icon">📚</span>
              <span>{{ set.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Sudah terdaftar -->
      <div v-else>
        <div class="course-header enrolled">
          <h1>{{ course.name }}</h1>

          <!-- Progress Bar -->
          <div class="progress-section">
            <div class="progress-info">
              <span>Progress Belajar</span>
              <span>{{ progressPercent }}%</span>
            </div>
            <div class="progress-bar">
              <div
                class="progress-fill"
                :style="{ width: progressPercent + '%' }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Sertifikat jika semua selesai -->
        <div class="certificate" v-if="progressPercent === 100">
          <div class="certificate-card">
            <h2>🎓 Selamat!</h2>
            <p>Kamu telah menyelesaikan course</p>
            <h3>{{ course.name }}</h3>
            <p class="cert-name">{{ user?.full_name }}</p>
            <p class="cert-date">{{ currentDate }}</p>
            <div class="cert-buttons">
              <button class="btn-home" @click="$router.push('/')">
                🏠 Kembali ke Home
              </button>
            </div>
          </div>
        </div>

        <!-- Daftar Sets & Lessons -->
        <div class="sets-container" v-else>
          <div class="set-block" v-for="set in course.sets" :key="set.id">
            <div class="set-header">
              <h2>{{ set.name }}</h2>
              <!-- Tombol Jump Here -->
              <button
                class="btn-jump"
                v-if="currentSetId === set.id"
                @click="jumpToSet(set)"
              >
                Jump Here
              </button>
            </div>

            <div class="lesson-list">
              <div
                class="lesson-item"
                v-for="lesson in set.lessons"
                :key="lesson.id"
                :class="getLessonStatus(lesson.id)"
                @click="goToLesson(lesson, set)"
              >
                <span class="lesson-icon">
                  {{ getLessonIcon(lesson.id) }}
                </span>
                <span class="lesson-name">{{ lesson.name }}</span>
                <span class="lesson-badge" :class="getLessonStatus(lesson.id)">
                  {{ getLessonStatus(lesson.id) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from "../services/api";

export default {
  name: "Course",
  data() {
    return {
      course: null,
      isEnrolled: false,
      completedLessonIds: [],
      loading: true,
      registering: false,
    };
  },
  computed: {
    user() {
      const u = localStorage.getItem("user");
      return u ? JSON.parse(u) : null;
    },

    currentDate() {
      return new Date().toLocaleDateString("id-ID", {
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    },

    // Semua lesson dari semua set
    allLessons() {
      if (!this.course) return [];
      return this.course.sets.flatMap((s) => s.lessons);
    },

    // Total lesson
    totalLessons() {
      return this.allLessons.length;
    },

    // Persentase progress
    progressPercent() {
      if (this.totalLessons === 0) return 0;
      return Math.round(
        (this.completedLessonIds.length / this.totalLessons) * 100,
      );
    },

    // Set yang berisi current lesson
    currentSetId() {
      const currentLesson = this.getCurrentLesson();
      if (!currentLesson) return null;
      const set = this.course.sets.find((s) =>
        s.lessons.some((l) => l.id === currentLesson.id),
      );
      return set?.id || null;
    },
  },
  methods: {
    async fetchData() {
      this.loading = true;
      try {
        const slug = this.$route.params.course_slug;

        // Ambil detail course
        const courseRes = await api.get(`/courses/${slug}`);
        this.course = courseRes.data.data;

        // Cek apakah sudah enrolled
        const progressRes = await api.get("/users/progress");
        const progress = progressRes.data.data.progress;
        const enrolled = progress.find((p) => p.course.slug === slug);

        if (enrolled) {
          this.isEnrolled = true;
          this.completedLessonIds = enrolled.completed_lessons.map((l) => l.id);
        }
      } catch (error) {
        console.error(error);
      } finally {
        this.loading = false;
      }
    },

    async registerCourse() {
      this.registering = true;
      try {
        const slug = this.$route.params.course_slug;
        await api.post(`/courses/${slug}/register`);
        await this.fetchData();
      } catch (error) {
        console.error(error);
      } finally {
        this.registering = false;
      }
    },

    // Dapatkan status lesson: completed, current, locked
    getLessonStatus(lessonId) {
      if (this.completedLessonIds.includes(lessonId)) {
        return "completed";
      }
      const currentLesson = this.getCurrentLesson();
      if (currentLesson?.id === lessonId) {
        return "current";
      }
      return "locked";
    },

    // Dapatkan lesson yang sedang berjalan
    getCurrentLesson() {
      return this.allLessons.find(
        (l) => !this.completedLessonIds.includes(l.id),
      );
    },

    getLessonIcon(lessonId) {
      const status = this.getLessonStatus(lessonId);
      if (status === "completed") return "✅";
      if (status === "current") return "▶️";
      return "🔒";
    },

    goToLesson(lesson, set) {
      const status = this.getLessonStatus(lesson.id);
      if (status === "locked") return;

      const slug = this.$route.params.course_slug;
      this.$router.push(`/${slug}/lessons/${lesson.id}`);
    },

    jumpToSet(set) {
      const currentLesson = set.lessons.find(
        (l) => this.getLessonStatus(l.id) === "current",
      );
      if (currentLesson) {
        const slug = this.$route.params.course_slug;
        this.$router.push(`/${slug}/jump/${currentLesson.id}`);
      }
    },
  },
  mounted() {
    this.fetchData();
  },
};
</script>

<style scoped>
.course-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 32px 24px;
}

.loading {
  text-align: center;
  padding: 60px;
  color: #666;
}

.course-header {
  margin-bottom: 32px;
}

.course-header h1 {
  font-size: 28px;
  color: #1f4e79;
  margin-bottom: 12px;
}

.description {
  color: #555;
  line-height: 1.6;
  margin-bottom: 20px;
}

.btn-register {
  background-color: #1f4e79;
  color: white;
  border: none;
  padding: 12px 28px;
  border-radius: 4px;
  font-size: 15px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-register:hover:not(:disabled) {
  background-color: #163d5f;
}

.btn-register:disabled {
  background-color: #999;
  cursor: not-allowed;
}

.outline {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.outline h2 {
  font-size: 18px;
  color: #1f4e79;
  margin-bottom: 16px;
}

.set-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.set-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: #f0f4f8;
  border-radius: 6px;
  font-size: 14px;
  color: #333;
}

.progress-section {
  margin-top: 16px;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: #555;
  margin-bottom: 6px;
}

.progress-bar {
  background: #e0e0e0;
  border-radius: 999px;
  height: 8px;
  overflow: hidden;
}

.progress-fill {
  background: #2e75b6;
  height: 100%;
  border-radius: 999px;
  transition: width 0.4s ease;
}

/* Certificate */
.certificate {
  display: flex;
  justify-content: center;
  margin: 40px 0;
}

.certificate-card {
  background: white;
  border: 3px solid #2e75b6;
  border-radius: 12px;
  padding: 48px;
  text-align: center;
  max-width: 500px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.certificate-card h2 {
  font-size: 32px;
  margin-bottom: 8px;
}

.certificate-card h3 {
  font-size: 22px;
  color: #1f4e79;
  margin: 12px 0;
}

.cert-name {
  font-size: 20px;
  font-weight: bold;
  color: #2e75b6;
  margin: 8px 0;
}

.cert-date {
  font-size: 13px;
  color: #999;
  margin-top: 12px;
}

/* Sets & Lessons */
.sets-container {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.set-block {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.set-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.set-header h2 {
  font-size: 18px;
  color: #1f4e79;
}

.btn-jump {
  background-color: #2e75b6;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  font-size: 13px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-jump:hover {
  background-color: #1f4e79;
}

.lesson-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.lesson-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 6px;
  font-size: 14px;
  transition: background 0.2s;
}

.lesson-item.completed {
  background: #f0fff4;
  cursor: pointer;
}

.lesson-item.current {
  background: #e8f4fd;
  cursor: pointer;
  border-left: 3px solid #2e75b6;
}

.lesson-item.locked {
  background: #f5f5f5;
  cursor: not-allowed;
  opacity: 0.6;
}

.lesson-name {
  flex: 1;
  color: #333;
}

.lesson-badge {
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 999px;
  font-weight: 500;
  text-transform: capitalize;
}

.lesson-badge.completed {
  background: #d4edda;
  color: #155724;
}

.lesson-badge.current {
  background: #bdd7ee;
  color: #1f4e79;
}

.lesson-badge.locked {
  background: #e0e0e0;
  color: #888;
}
</style>
