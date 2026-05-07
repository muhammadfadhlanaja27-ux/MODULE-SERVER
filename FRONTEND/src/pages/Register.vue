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

<style scoped>
.register-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #F0F4F8;
}

.register-card {
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 400px;
}

h1 {
    text-align: center;
    color: #1F4E79;
    font-size: 24px;
    margin-bottom: 4px;
}

h2 {
    text-align: center;
    color: #444;
    font-size: 18px;
    margin-bottom: 24px;
    font-weight: normal;
}

.form-group {
    margin-bottom: 16px;
}

label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #CCC;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
    transition: border 0.2s;
}

input:focus {
    outline: none;
    border-color: #2E75B6;
}

.input-error {
    border-color: #C00000 !important;
}

.error-text {
    color: #C00000;
    font-size: 12px;
    margin-top: 4px;
    display: block;
}

.alert-error {
    background-color: #FDECEA;
    color: #C00000;
    padding: 10px 14px;
    border-radius: 4px;
    margin-bottom: 16px;
    font-size: 14px;
    border-left: 4px solid #C00000;
}

.btn-register {
    width: 100%;
    padding: 12px;
    background-color: #1F4E79;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 8px;
    transition: background 0.2s;
}

.btn-register:hover:not(:disabled) {
    background-color: #163D5F;
}

.btn-register:disabled {
    background-color: #999;
    cursor: not-allowed;
}

.login-link {
    text-align: center;
    margin-top: 16px;
    font-size: 14px;
    color: #666;
}

.login-link a {
    color: #2E75B6;
    text-decoration: none;
    font-weight: 500;
}

.login-link a:hover {
    text-decoration: underline;
}
</style>