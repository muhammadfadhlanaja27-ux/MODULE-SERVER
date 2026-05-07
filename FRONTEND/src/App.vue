<template>
    <div>
        <Navbar v-if="isLoggedIn" />
        <router-view />
    </div>
</template>

<script>
import Navbar from './components/Navbar.vue'

export default {
    components: { Navbar },
    computed: {
        isLoggedIn() {
            return !!localStorage.getItem('token')
        }
    }
}
</script>