<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount, nextTick, computed, watch } from 'vue';
import ToastHost from '@/Components/ToastHost.vue';
import SyncIndicator from '@/Components/SyncIndicator.vue';

const page = usePage();

function getRoleBadgeClass(roleName) {
    const badges = {
        'admin': 'bg-danger',
        'manager': 'bg-primary',
        'staff': 'bg-success',
        'viewer': 'bg-secondary',
    };
    return badges[roleName] || 'bg-info';
}

function initSidebar() {
    if (!window.jQuery) return;
    const $ = window.jQuery;
    // metisMenu init dipindah ke highlightActive() — biar setiap navigasi
    // dispose & re-init bersih. Di sini cuma bind tombol toggle sidebar.
    $('#vertical-menu-btn').off('click.layout').on('click.layout', function (e) {
        e.preventDefault();
        const body = document.body;
        if (window.innerWidth >= 992) {
            body.classList.toggle('vertical-collpsed');
            body.classList.toggle('sidebar-enable');
        } else {
            body.classList.toggle('sidebar-enable');
        }
    });
}

function toggleFullscreen() {
    const doc = document;
    const isFs = doc.fullscreenElement || doc.webkitFullscreenElement || doc.mozFullScreenElement || doc.msFullscreenElement;
    if (!isFs) {
        const el = doc.documentElement;
        const req = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
        if (req) req.call(el);
    } else {
        const exit = doc.exitFullscreen || doc.webkitExitFullscreen || doc.mozCancelFullScreen || doc.msExitFullscreen;
        if (exit) exit.call(doc);
    }
}

function highlightActive() {
    if (!window.jQuery) return;
    const $ = window.jQuery;
    const path = window.location.pathname;
    const $menu = $('#side-menu');
    if (!$menu.length) return;

    // Dispose metisMenu dulu — supaya inline style="height:..." yang dia pasang
    // untuk animasi collapse/expand bisa kita reset. Tanpa ini, submenu lama
    // tetap punya height fix dan menimpa item lain (kelihatan overlap).
    if ($menu.data('metisMenu')) {
        try { $menu.metisMenu('dispose'); } catch (e) {}
    }

    // Reset visual state penuh: class + inline style yang ditinggalkan metisMenu.
    $menu.find('a').removeClass('active');
    $menu.find('ul.sub-menu').removeClass('mm-show mm-collapse mm-collapsing in').removeAttr('style');
    $menu.find('li.mm-active').removeClass('mm-active');

    // Pilih match paling spesifik (href terpanjang). Trailing slash supaya
    // /barang tidak false-match untuk /barangmasuk dst.
    let best = null;
    $menu.find('a').each(function () {
        const href = $(this).attr('href');
        if (!href || href === '#' || href.startsWith('javascript')) return;
        const isMatch = path === href || (href !== '/' && path.startsWith(href + '/'));
        if (isMatch && (!best || href.length > best.href.length)) {
            best = { el: this, href };
        }
    });

    if (best) {
        const $el = $(best.el).addClass('active');
        $el.parents('ul.sub-menu').addClass('mm-show');
        $el.parents('li').addClass('mm-active');
    }

    // Re-init metisMenu — dia akan baca state class sekarang sebagai initial.
    if (typeof $menu.metisMenu === 'function') $menu.metisMenu();
}

let removeNavListener = null;

onMounted(async () => {
    await nextTick();
    initSidebar();
    highlightActive();
    if (window.Waves && typeof window.Waves.init === 'function') {
        try { window.Waves.init(); } catch (e) {}
    }

    removeNavListener = router.on('navigate', () => {
        nextTick(highlightActive);
    });

    // Warmup prefetch (1-by-1 supaya tidak menyaturasi PHP).
    const warmupRoutes = [
        '/category', '/merk', '/group', '/gudang', '/supplier', '/barang', '/stok',
        '/barang-masuk', '/barang-keluar', '/penyesuaian-stok',
        '/riwayat/semua', '/riwayat/barang-masuk', '/riwayat/barang-keluar', '/riwayat/penyesuaian'
    ];
    const warmup = async () => {
        for (const url of warmupRoutes) {
            if (window.location.pathname === url) continue;
            try { await router.prefetch(url, { method: 'get' }, { cacheFor: '5m' }); }
            catch (e) {}
        }
    };
    if ('requestIdleCallback' in window) requestIdleCallback(warmup, { timeout: 2000 });
    else setTimeout(warmup, 1000);
});

onBeforeUnmount(() => {
    if (removeNavListener) removeNavListener();
});
</script>

<template>
    <div id="layout-wrapper">
        <header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex">
                    <div class="navbar-brand-box">
                        <Link href="/" class="logo logo-dark">
                            <span class="logo-sm"><img src="/assets/images/logo.svg" alt="" height="22"></span>
                            <span class="logo-lg"><img src="/assets/images/logo-dark.png" alt="" height="17"></span>
                        </Link>
                        <Link href="/" class="logo logo-light">
                            <span class="logo-sm"><img src="/assets/images/logo-light.svg" alt="" height="22"></span>
                            <span class="logo-lg"><img src="/assets/images/logo-light.png" alt="" height="19"></span>
                        </Link>
                    </div>
                    <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                        <i class="fa fa-fw fa-bars"></i>
                    </button>
                </div>
                <div class="d-flex">
                    <div class="dropdown d-none d-lg-inline-block ms-1">
                        <button type="button" class="btn header-item noti-icon waves-effect" @click="toggleFullscreen">
                            <i class="bx bx-fullscreen"></i>
                        </button>
                    </div>

                    <div class="dropdown d-inline-block">
                        <button type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown">
                            <img class="rounded-circle header-profile-user" src="/assets/images/users/avatar-1.jpg" alt="Header Avatar">
                            <span class="d-none d-xl-inline-block ms-1">{{ page.props.auth?.user?.name ?? 'User' }}</span>
                            <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <!-- User Info -->
                            <div class="dropdown-header noti-title">
                                <h6 class="text-overflow m-0">Welcome!</h6>
                            </div>
                            <div class="dropdown-item-text">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-2">
                                        <img class="rounded-circle avatar-xs" src="/assets/images/users/avatar-1.jpg" alt="">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ page.props.auth?.user?.name ?? 'User' }}</h6>
                                        <p class="text-muted mb-0 font-size-11">
                                            <span class="badge" :class="getRoleBadgeClass(page.props.auth?.role?.name)">
                                                {{ page.props.auth?.role?.display_name ?? page.props.auth?.role?.name ?? 'No Role' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#">
                                <i class="bx bx-user font-size-16 align-middle me-1"></i>Profil Saya
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="bx bx-wallet font-size-16 align-middle me-1"></i>Dompet
                            </a>
                            <a class="dropdown-item d-block" href="#">
                                <span class="badge bg-success float-end">11</span>
                                <i class="bx bx-wrench font-size-16 align-middle me-1"></i>Pengaturan
                            </a>
                            <a class="dropdown-item" href="#">
                                <i class="bx bx-lock-open font-size-16 align-middle me-1"></i>Kunci Layar
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#"
                                onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i>Keluar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="vertical-menu">
            <div data-simplebar class="h-100">
                <div id="sidebar-menu">
                    <ul class="metismenu list-unstyled" id="side-menu">
                        <li>
                            <Link href="/dashboard" prefetch cache-for="15m" class="waves-effect">
                                <i class="bx bx-home-alt"></i><span>Dashboard</span>
                            </Link>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="has-arrow waves-effect">
                                <i class="bx bx-package"></i><span>Data Master</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                             <li><Link href="/barang"   prefetch cache-for="15m">Barang</Link></li>
                                <li><Link href="/category" prefetch cache-for="15m">Kategori</Link></li>
                                <li><Link href="/sub-category" prefetch cache-for="15m">Sub Kategori</Link></li>
                                <li><Link href="/merk"     prefetch cache-for="15m">Merk</Link></li>
                                <li><Link href="/group"    prefetch cache-for="15m">Group</Link></li>
                                <li><Link href="/gudang"   prefetch cache-for="15m">Gudang</Link></li>
                                <li><Link href="/supplier" prefetch cache-for="15m">Supplier</Link></li>
                            </ul>
                        </li>
                        <li>
                            <Link href="/stok" class="waves-effect">
                                <i class="bx bx-buildings"></i><span>Stok Gudang</span>
                            </Link>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="has-arrow waves-effect">
                                <i class="bx bx-transfer"></i><span>Transaksi</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><Link href="/barang-masuk" prefetch cache-for="15m">Barang Masuk</Link></li>
                                <li><Link href="/barang-keluar" prefetch cache-for="15m">Barang Keluar</Link></li>
                                <li><Link href="/penyesuaian-stok" prefetch cache-for="15m">Penyesuaian Stok</Link></li>
                            </ul>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="has-arrow waves-effect">
                                <i class="bx bx-history"></i><span>Riwayat</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><Link href="/riwayat/semua" prefetch cache-for="15m">Semua Mutasi</Link></li>
                                <li><Link href="/riwayat/barang-masuk" prefetch cache-for="15m">Riwayat Barang Masuk</Link></li>
                                <li><Link href="/riwayat/barang-keluar" prefetch cache-for="15m">Riwayat Barang Keluar</Link></li>
                                <li><Link href="/riwayat/penyesuaian" prefetch cache-for="15m">Riwayat Penyesuaian</Link></li>
                            </ul>
                        </li>
                        <li>
                            <Link href="/laporan-keuntungan" prefetch cache-for="15m" class="waves-effect">
                                <i class="bx bx-line-chart"></i><span>Laporan Keuntungan</span>
                            </Link>
                        </li>
                        
                        <!-- User Management — admin atau punya permission user.view / role.view -->
                        <li v-if="page.props.auth?.isAdmin || (page.props.auth?.permissions || []).some(p => ['user.view','role.view'].includes(p))">
                            <a href="javascript:void(0);" class="has-arrow waves-effect">
                                <i class="bx bx-user-circle"></i><span>User Management</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li v-if="page.props.auth?.isAdmin || (page.props.auth?.permissions || []).includes('user.view')"><Link href="/user">Kelola User</Link></li>
                                <li v-if="page.props.auth?.isAdmin || (page.props.auth?.permissions || []).includes('role.view')"><Link href="/role">Role & Permission</Link></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <Transition name="page-fade" mode="out-in">
                        <div :key="page.url" class="page-fade-wrap">
                            <slot />
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </div>

    <form id="logout-form" method="POST" action="/logout" class="d-none">
        <input type="hidden" name="_token" :value="page.props.csrf_token ?? ''">
    </form>

    <ToastHost />
    <SyncIndicator />
</template>

<style scoped>
.dropdown-item-text {
    padding: 0.5rem 1rem;
}

.dropdown-header {
    padding: 0.5rem 1rem;
}

.avatar-xs {
    height: 2rem;
    width: 2rem;
}

.header-profile-user {
    height: 36px;
    width: 36px;
    background-color: #f8f9fa;
    padding: 3px;
}
</style>

<!-- <style>
.page-fade-wrap {
    will-change: opacity;
}
.page-fade-enter-active {
    transition: opacity 160ms ease-out;
}
.page-fade-leave-active {
    transition: opacity 100ms ease-in;
}
.page-fade-enter-from,
.page-fade-leave-to {
    opacity: 0;
}
</style> -->
