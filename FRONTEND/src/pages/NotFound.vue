<template>
    <div class="notfound-container">
        <div class="notfound-card">
            <div class="error-code">404</div>
            <h1>Page Not Found</h1>
            <p>Halaman yang kamu cari tidak ditemukan.</p>
            <button class="btn-home" @click="$router.push('/')">
                Back to Home
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'NotFound'
}
</script>