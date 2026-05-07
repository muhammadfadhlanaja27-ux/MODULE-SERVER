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

<style scoped>
.notfound-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #F0F4F8;
}

.notfound-card {
    background: white;
    padding: 60px 48px;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.1);
    text-align: center;
    max-width: 400px;
    width: 100%;
}

.error-code {
    font-size: 96px;
    font-weight: bold;
    color: #2E75B6;
    line-height: 1;
    margin-bottom: 16px;
}

h1 {
    font-size: 24px;
    color: #1F4E79;
    margin-bottom: 12px;
}

p {
    color: #666;
    font-size: 15px;
    margin-bottom: 32px;
}

.btn-home {
    background-color: #1F4E79;
    color: white;
    border: none;
    padding: 12px 32px;
    border-radius: 4px;
    font-size: 15px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-home:hover {
    background-color: #163D5F;
}
</style>