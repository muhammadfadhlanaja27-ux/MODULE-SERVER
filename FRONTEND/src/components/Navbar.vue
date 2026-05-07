<template>
    <nav class="navbar">
        <div class="navbar-brand">
            <router-link to="/">Web Tech Studio</router-link>
        </div>
        <div class="navbar-menu">
            <span class="navbar-user">{{ user?.full_name }}</span>
            <button class="btn-logout" @click="logout">Logout</button>
        </div>
    </nav>
</template>

<script>
import api from '../services/api'

export default {
    name: 'Navbar',
    computed: {
        user() {
            const userData = localStorage.getItem('user')
            return userData ? JSON.parse(userData) : null
        }
    },
    methods: {
        async logout() {
            try {
                await api.post('/logout')
            } catch (e) {
                console.error(e)
            } finally {
                localStorage.removeItem('token')
                localStorage.removeItem('user')
                this.$router.push('/login')
            }
        }
    }
}
</script>