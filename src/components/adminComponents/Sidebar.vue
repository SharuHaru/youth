<template>
    <v-navigation-drawer 
    app 
    :width="drawerWidth" 
    :permanent="true"
    :disable-resize-watcher="true"
    :mobile-breakpoint="0"
    class="relative d-flex flex-col items-center pa-3 pb-7 border elevation-10 rounded-2xl ma-3"
    :class="!isDarkMode  ? '' : 'dark-gradient'"
    :style="this.drawer ? {'height' : '98vh'} : {'border-radius' : '1rem', 'height' : '98vh'}"

        <!-- Toggle Button Fixed at the Top Right -->
        <v-btn
            :class= "this.drawer ? 'toggle-drawer' : 'w-full'"
            @click="toggleDrawer"
        >
            <v-icon>{{ drawer ? 'mdi-menu-open' : 'mdi-menu' }}</v-icon>
        </v-btn>

        <!-- Logo and Barangay Name -->
        <v-list-item class="logo-container">
            <v-divider
            class="mt-4"
            v-if="!this.drawer"></v-divider>
            <div class="d-flex justify-center items-center py-5" @click="openBarangayWebsite">
                <v-avatar size="45">
                    <v-img :src="$store.getters['base'] + 'public/Group.svg'" alt="Barangay Logo"></v-img>
                </v-avatar>

                <v-list-item
                v-if="drawer">
                    <v-list-item-title>BARANGAY</v-list-item-title>
                    <v-list-item-subtitle>{{ barangaySlug.toUpperCase() }}</v-list-item-subtitle>
                </v-list-item>
            </div>
            
                <v-divider
                v-if="!this.drawer"></v-divider>
        </v-list-item>

        <!-- Navigation Menu (Names and Icons)-->
        <v-list 
        v-if="drawer"
        density="compact" 
        class="d-flex flex-col ga-3 w-full pa-0"
        nav>
            <v-list-item
                v-for="menuObj in menuObjs"
                :key="menuObj.menuName"
                :to="menuObj.to"
                class="pa-3"
                :class="{ active: isActive(menuObj.to) }"
                density="compact"
                @click="navigate(menuObj.to)"
            >
                <template v-slot:prepend class="">
                    <v-icon>{{ menuObj.icon }}</v-icon>
                </template>

                <v-list-item-title
                    v-if="drawer"
                    class="item"
                    style="font-size: 1rem;
                    height: auto;"
                >
                    {{ menuObj.menuName }}
                </v-list-item-title>
            </v-list-item>
        </v-list>

        <!-- Navigation Menu (Icons Only)-->
        <v-list 
        v-if="!drawer"
        density="compact" 
        nav>
            <v-list-item
                v-for="menuObj in menuObjs"
                :key="menuObj.menuName"
                :to="menuObj.to"
                :class="{ active: isActive(menuObj.to) }"
                density="compact"
                class="mb-6 d-flex justify-center items-center"
                @click="navigate(menuObj.to)"
            >
                <v-icon>{{ menuObj.icon }}</v-icon>
            </v-list-item>
        </v-list>

        <!-- Go to Barangay Website Button -->
        <v-btn
            v-if="drawer"
            class="d-flex items-center justify-center w-auto ma-auto"
            height="64"
            @click="openBarangayWebsite"
        >
            GO TO WEBSITE
        </v-btn>

        <!-- Logout Button at the Bottom Center -->
        <div 
        v-if="drawer"
        class="logout absolute bottom-0 d-flex items-center justify-center w-[250px] py-10 gap-5">
            <ThemeSwitcher class="w-[10%]" />

            <v-btn class="pa-3 w-[50%] d-flex justify-center items-center" color="error" variant="outlined" @click="logout">
                <v-icon left>mdi-logout</v-icon>
                <span>LOG OUT</span>
            </v-btn>
        </div>

        <!-- Logout Button at the Bottom Center (Icons Only) -->
        <div 
        v-if="!drawer"
        class="absolute bottom-0 d-flex flex-col items-center justify-center gap-5 my-5">
            <ThemeSwitcher/>
            <v-btn class="d-flex justify-center items-center" color="error" variant="outlined" @click="logout">
                <v-icon>mdi-logout</v-icon>
            </v-btn>
        </div>
    </v-navigation-drawer>
</template>
  
<script>
import ThemeSwitcher from '../ThemeSwitcher.vue';
import $ from 'jquery';

import { useTheme } from 'vuetify';

export default {
    components: { ThemeSwitcher },

    data() {
        return {
            drawer: true,
            windowWidth: window.innerWidth // track screen width
        };
    },
    setup() {
        const theme = useTheme();
        return { theme };
    },

    computed: {
        isDarkMode() {
            return this.theme.current.value.dark;
        },

        barangaySlug() {
            const user = this.$store.getters['auth/getUser'];
            return user && user.barangay ? user.barangay.slug : 'default-slug';
        },

        menuObjs() {
            return [
                { menuName: "Dashboard", icon: "mdi-view-dashboard", to: `/admin/${this.barangaySlug}/dashboard` },
                { menuName: "Officials", icon: "mdi-account-group", to: `/admin/${this.barangaySlug}/officials` },
                { menuName: "Announcements", icon: "mdi-bullhorn", to: `/admin/${this.barangaySlug}/announcements` },
                { menuName: "Achievements", icon: "mdi-trophy", to: `/admin/${this.barangaySlug}/achievements` },
                { menuName: "Settings and Profile", icon: "mdi-cog", to: `/admin/${this.barangaySlug}/settings` },
                { menuName: "Notices", icon: "mdi-information", to: `/admin/${this.barangaySlug}/notices` },
            ];
        },

        drawerWidth() {
            if (this.windowWidth < 960) {
                return 100;
            }
            return this.drawer ? 280 : 100;
        }
    },

    mounted() {
        window.addEventListener("resize", this.updateWidth);
        this.updateWidth();

        const savedDrawer = localStorage.getItem('admin_sidebar_open');
        if (savedDrawer !== null && this.windowWidth >= 960) {
            this.drawer = JSON.parse(savedDrawer);
        }
    },


    beforeUnmount() {
        window.removeEventListener("resize", this.updateWidth);
    },

    methods: {
        updateWidth() {
            this.windowWidth = window.innerWidth;

            if (this.windowWidth < 960) {
                this.drawer = false;
            }
        },
        toggleDrawer() {
            if (this.windowWidth >= 960) {
                this.drawer = !this.drawer;
                localStorage.setItem('admin_sidebar_open', JSON.stringify(this.drawer));
            }
        },

        navigate(path) {
            this.$router.push(path);
        },

        logout() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            $.ajax({
                type: 'POST',
                xhrFields: { withCredentials: true },
                url: `${this.$store.getters['api_base']}?e=auth&a=logout`,
                headers: { 'X-CSRF-Token': csrfToken },
                success: () => {
                    this.$store.commit('auth/setUser', null);
                    this.$router.replace({ name: 'login' });
                },
                error: (error) => console.error("Logout error:", error)
            });
        },

        isActive(route) {
            return this.$route.path.startsWith(route);
        },

        openBarangayWebsite() {
            const resolved = this.$router.resolve({
                name: 'barangay-landingpage',
                params: { slug: this.barangaySlug }
            });
            window.open(resolved.href, '_blank');
        }
    }
};
</script>


<style scoped>
.active {
    background-color: rgba(255, 255, 255, 0.1);
}

.v-navigation-drawer {
    display: flex;
    flex-direction: column;
    transition: width 0.3s;
}

/* Toggle Button Styles */
.toggle-drawer {
    position: absolute;
    margin: 5px;
    top: 0;
    right: 0;
    z-index: 2000;
}

.dark-gradient {
  background-image: linear-gradient(
    45deg,
    #363636,
    #0e0e0e,
    #363636,
    #0e0e0e
  );
  background-size: 100% 100%;
}
</style>
