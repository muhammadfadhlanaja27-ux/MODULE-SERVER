<template>
  <div class="login-container">
    <div class="login-card">
      <h1>Web Tech Studio</h1>
      <h2>Login</h2>

      <!-- Error Message -->
      <div class="alert-error" v-if="errorMessage">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="login">
        <div class="form-group">
          <label>Username</label>
          <input
            v-model="form.username"
            type="text"
            placeholder="Masukkan username"
            :class="{ 'input-error': errors.username }"
          />
          <span class="error-text" v-if="errors.username">
            {{ errors.username[0] }}
          </span>
        </div>

        <div class="form-group">
          <label>Password</label>
          <input
            v-model="form.password"
            type="password"
            placeholder="Masukkan password"
            :class="{ 'input-error': errors.password }"
          />
          <span class="error-text" v-if="errors.password">
            {{ errors.password[0] }}
          </span>
        </div>

        <button type="submit" class="btn-login" :disabled="loading">
          {{ loading ? "Loading..." : "Login" }}
        </button>
      </form>

      <p class="register-link">
        Belum punya akun?
        <router-link to="/register">Daftar disini</router-link>
      </p>
    </div>
  </div>
</template>

<script>
import api from "../services/api";

export default {
  name: "Login",
  data() {
    return {
      form: {
        username: "",
        password: "",
      },
      errors: {},
      errorMessage: "",
      loading: false,
    };
  },
  methods: {
    async login() {
      this.errors = {};
      this.errorMessage = "";
      this.loading = true;

      try {
        const response = await api.post("/login", this.form);
        const { data } = response.data;

        // Simpan token dan data user ke localStorage
        localStorage.setItem("token", data.token);
        localStorage.setItem("user", JSON.stringify(data));

        // Redirect ke home
        // Redirect berdasarkan role
        if (data.role === "admin") {
          this.$router.push("/admin");
        } else {
          this.$router.push("/");
        }
      } catch (error) {
        const response = error.response;

        if (response?.status === 400) {
          if (response.data.errors) {
            this.errors = response.data.errors;
          } else {
            this.errorMessage = response.data.message;
          }
        } else {
          this.errorMessage = "Terjadi kesalahan, coba lagi";
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
