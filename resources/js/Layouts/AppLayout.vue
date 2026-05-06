<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { onMounted, onBeforeUnmount, nextTick, computed, watch } from 'vue';
import ToastHost from '@/Components/ToastHost.vue';

const page = usePage();

function initSidebar() {
    if (!window.jQuery) return;
    const $ = window.jQuery;
    const $menu = $('#side-menu');
    if (!$menu.length) return;

    if ($menu.data('metisMenu')) {
        try { $menu.metisMenu('dispose'); } catch (e) {}
    }
    if (typeof $menu.metisMenu === 'function') $menu.metisMenu();

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
    $('#side-menu a').removeClass('active');
    $('#side-menu a').each(function () {
        const href = $(this).attr('href');
        if (!href || href === '#' || href.startsWith('javascript')) return;
        if (path === href || (href !== '/' && path.startsWith(href))) {
            $(this).addClass('active');
            $(this).parents('ul.sub-menu').addClass('mm-show').parent().addClass('mm-active');
        }
    });
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
    const warmupRoutes = ['/category', '/merk', '/group', '/barang'];
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
                            <span class="d-none d-xl-inline-block ms-1">{{ page.props.auth?.user?.name ?? 'User' }}</span>
                            <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="#">
                                <i class="bx bx-user font-size-16 align-middle me-1"></i>Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#"
                                onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i>Logout
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
                            <Link href="/dashboard" prefetch cache-for="5m" class="waves-effect">
                                <i class="bx bx-home-alt"></i><span>Dashboard</span>
                            </Link>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="has-arrow waves-effect">
                                <i class="bx bx-package"></i><span>Data Master</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><Link href="/barang"   prefetch cache-for="5m">Barang</Link></li>
                                <li><Link href="/category" prefetch cache-for="5m">Kategori</Link></li>
                                <li><Link href="/merk"     prefetch cache-for="5m">Merk</Link></li>
                                <li><Link href="/group"    prefetch cache-for="5m">Group</Link></li>
                            </ul>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="has-arrow waves-effect">
                                <i class="bx bx-transfer"></i><span>Transaksi</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><Link href="/barangmasuk"  prefetch cache-for="5m">Barang Masuk</Link></li>
                                <li><Link href="/barangkeluar" prefetch cache-for="5m">Barang Keluar</Link></li>
                            </ul>
                        </li>
                        <li>
                            <Link href="/pr" prefetch cache-for="5m" class="waves-effect">
                                <i class="bx bx-receipt"></i><span>Pre-Order</span>
                            </Link>
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
</template>

<style>
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
</style>
