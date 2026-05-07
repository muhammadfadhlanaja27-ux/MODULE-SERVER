<template>
    <div class="register-container">
        <div class="register-card">
            <h1>Web Tech Studio</h1>
            <h2>Daftar Akun</h2>

            <!-- Error Message -->
            <div class="alert-error" v-if="errorMessage">
                {{ errorMessage }}
            </div>

            <form @submit.prevent="register">
                <div class="form-group">
                    <label>Full Name</label>
                    <input
                        v-model="form.full_name"
                        type="text"
                        placeholder="Masukkan nama lengkap"
                        :class="{ 'input-error': errors.full_name }"
                    />
                    <span class="error-text" v-if="errors.full_name">
                        {{ errors.full_name[0] }}
                    </span>
                </div>

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
                        placeholder="Minimal 6 karakter"
                        :class="{ 'input-error': errors.password }"
                    />
                    <span class="error-text" v-if="errors.password">
                        {{ errors.password[0] }}
                    </span>
                </div>

                <button type="submit" class="btn-register" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Daftar' }}
                </button>
            </form>

            <p class="login-link">
                Sudah punya akun?
                <router-link to="/login">Login disini</router-link>
            </p>
        </div>
    </div>
</template>

<script>
import api from '../services/api'

export default {
    name: 'Register',
    data() {
        return {
            form: {
                full_name: '',
                username: '',
                password: ''
            },
            errors: {},
            errorMessage: '',
            loading: false
        }
    },
    methods: {
        async register() {
            this.errors = {}
            this.errorMessage = ''
            this.loading = true

            try {
                const response = await api.post('/register', this.form)
                const { data } = response.data

                // Simpan token dan data user ke localStorage
                localStorage.setItem('token', data.token)
                localStorage.setItem('user', JSON.stringify(data))

                // Redirect ke home setelah register
                this.$router.push('/')

            } catch (error) {
                const response = error.response

                if (response?.status === 400) {
                    if (response.data.errors) {
                        this.errors = response.data.errors
                    } else {
                        this.errorMessage = response.data.message
                    }
                } else {
                    this.errorMessage = 'Terjadi kesalahan, coba lagi'
                }
            } finally {
                this.loading = false
            }
        }
    }
}
</script>