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
            <progress :value="progressPercent" max="100"></progress>
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
