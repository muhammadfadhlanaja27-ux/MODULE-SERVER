import { createRouter, createWebHistory } from 'vue-router'

import Login from '../pages/Login.vue'
import Register from '../pages/Register.vue'
import Home from '../pages/Home.vue'
import AdminDashboard from '../pages/AdminDashboard.vue'
import Course from '../pages/Course.vue'
import Lesson from '../pages/Lesson.vue'
import Jump from '../pages/Jump.vue'
import NotFound from '../pages/NotFound.vue'

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { guestOnly: true }
    },
    {
        path: '/register',
        name: 'Register',
        component: Register,
        meta: { guestOnly: true }
    },
    {
        path: '/',
        name: 'Home',
        component: Home,
        meta: { requiresAuth: true, role: 'user' }
    },
    {
        path: '/admin',
        name: 'AdminDashboard',
        component: AdminDashboard,
        meta: { requiresAuth: true, role: 'admin' }
    },
    {
        path: '/:course_slug',
        name: 'Course',
        component: Course,
        meta: { requiresAuth: true }
    },
    {
        path: '/:course_slug/lessons/:lesson_id',
        name: 'Lesson',
        component: Lesson,
        meta: { requiresAuth: true }
    },
    {
        path: '/:course_slug/jump/:lesson_id',
        name: 'Jump',
        component: Jump,
        meta: { requiresAuth: true }
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: NotFound
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// Navigation Guard
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')
    const user  = localStorage.getItem('user')
    const role  = user ? JSON.parse(user).role : null

    // Halaman yang butuh login
    if (to.meta.requiresAuth && !token) {
        next('/login')
        return
    }

    // Halaman yang hanya untuk guest
    if (to.meta.guestOnly && token) {
        // Redirect sesuai role
        if (role === 'admin') {
            next('/admin')
        } else {
            next('/')
        }
        return
    }

    // Proteksi halaman berdasarkan role
    if (to.meta.role && to.meta.role !== role) {
        if (role === 'admin') {
            next('/admin')
        } else {
            next('/')
        }
        return
    }

    next()
})

export default router