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

<style scoped>
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 32px;
    background-color: #1F4E79;
    color: white;
}

.navbar-brand a {
    color: white;
    text-decoration: none;
    font-size: 20px;
    font-weight: bold;
}

.navbar-menu {
    display: flex;
    align-items: center;
    gap: 16px;
}

.navbar-user {
    color: #BDD7EE;
    font-size: 14px;
}

.btn-logout {
    background-color: #C00000;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.btn-logout:hover {
    background-color: #9B0000;
}
</style>