<template>
    <v-sheet class="social-links d-flex flex-col justify-center items-center" elevation="10">
      <div class="text-xs text-grey italic uppercase center">
        {{ title }}
      </div>  
      <v-sheet class="d-flex flex-row justify-evenly items-center flex-wrap w-full ga-5">
        <v-btn 
        v-for="socialMediaLink in socialMediaLinks"
        icon 
        :href="socialMediaLink.url" target="_blank" rel="noopener noreferrer" size="30">
          <v-avatar 
          :image="$store.getters.base + socialMediaLink.iconPath"
          size="30"></v-avatar>      
        </v-btn>

        <v-btn icon size="30">
          <v-icon>mdi-link-variant</v-icon>
        </v-btn>
      </v-sheet>
    </v-sheet>
  
    
  </template>
  
  <script>
  import ThemeSwitcher from '../ThemeSwitcher.vue';
  
  export default {
    name: 'SocialLinks',
    props: {
      title : {
        type: String,
        default: 'Social Links',
      },
      socialMediaLinks: {
        type: Object,
        default: () => ({
          facebook: {
            iconPath: 'fb.png',
          },
          instagram: {
            iconPath: 'insta.png',
          },
          youtube: {
            iconPath: 'yt.png',
          },
        }),
      },
    },
    components: { ThemeSwitcher },
    data() {
      return {}
    },
    computed: {
    },
    methods: {
    }
  };
  </script>
  
  <style scoped>
  .social-links {
    /* position: fixed;
    bottom: 5%;
    left: 5%; */

    /* position: relative; */
  


    z-index: 3;
    padding: 1rem 1.5rem;
    border-radius: 1rem;
    height: auto;
  }

  </style>
  