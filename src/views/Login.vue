<script>
import $ from "jquery";
import debounce from "lodash/debounce";

export default {
  name: "Login",
  data() {
    return {
      username: "",
      password: "",
      loading: false,
      usernameError: "",
      passwordError: "",
      requestError: "",
      backgroundImage: "", // set dynamically in mounted

      // Data for Forgot Password Feature
      forgotPassword: false,
      gmailForgotPassword: "",
      otp: null,
      sendOtp: true,
      verifyOtp: false,
      resetPassword: false,
      resetToken: '',
      newPassword: '',
      reEnterPassword: ''
    };
  },
  mounted() {
    // 🎨 Background gradient based on theme
    const isDark = this.$vuetify.theme.global.current.dark;
    this.backgroundImage = isDark
      ? "linear-gradient(45deg, #363636, #0e0e0e, #363636, #0e0e0e)"
      : "linear-gradient(45deg, #f0f0f0, #ffffff)";

    // 🎯 Handle OAuth redirect with ?code=...
    const params = new URLSearchParams(window.location.search);
    const code = params.get("code");
    const state = params.get("state");

    if(code) {
      this.loading = true
      $.ajax({
        url: `${this.$store.getters["api_base"]}?e=auth&a=process-${state}`,
        type: "POST",
        xhrFields: { withCredentials: true },
        headers: {
          "X-CSRF-Token": document.querySelector("meta[name='csrf-token']").content,
        },
        data: { code },
        success: (res) => {
          if (res.success) {
            console.log(res);
            this.$store.commit("auth/setUser", res.data.barangay);
            const barangaySlug = res?.data?.barangay?.slug;
            if (barangaySlug) {
              this.$router.replace({ name: "admin-dashboard", params: { barangaySlug } });
            } else {
              this.$router.replace({ name: "admin-dashboard" });
            }
          } else {
            console.log("OAuth failed:", res.message);
          }
        },
        error: (jqXHR, textStatus, errorThrown) => {
            if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
              this.requestError = jqXHR.responseJSON.message;
            }
        },
        complete: () => {
          this.loading = false;
        }
      });
    }
  },
  methods: {
    validateForm() {
      let valid = true;
      this.usernameError = "";
      this.passwordError = "";

      if (!this.username.trim()) {
        this.usernameError = "Username is required";
        valid = false;
      }
      if (!this.password.trim()) {
        this.passwordError = "Password is required";
        valid = false;
      }
      return valid;
    },

    handleSubmit: debounce(function () {
      if (!this.validateForm()) return;

      this.loading = true;
      this.requestError = "";
      const csrfToken = document.querySelector("meta[name='csrf-token']")?.content || "";

      if (!csrfToken) {
        this.requestError = "CSRF token missing. Please refresh the page.";
        this.loading = false;
        return;
      }

      $.ajax({
        url: `${this.$store.getters["api_base"]}?e=auth&a=login`,
        type: "POST",
        xhrFields: { withCredentials: true },
        data: {
          identifier: this.username,
          password: this.password,
        },
        headers: { "X-CSRF-Token": csrfToken },
        success: (data) => {
          this.$store.commit("auth/setUser", data.data);
          const barangaySlug = data?.data?.barangay?.slug;
          if (!barangaySlug) {
            this.requestError = "Invalid response from server. Missing barangay slug.";
            return;
          }
          this.$router.replace({
            name: "admin-dashboard",
            params: { barangaySlug },
          });
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error("Error:", textStatus, errorThrown);
          let errorMsg = "An error occurred while processing your request.";
          if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
            errorMsg = jqXHR.responseJSON.message;
          } else if (jqXHR.responseText) {
            errorMsg = jqXHR.responseText;
          }
          this.requestError = errorMsg;
        },
        complete: () => {
          this.loading = false;
        },
      });
    }, 300),


    // Handles OAuth Login using Facebook or Google Account
    loginWithGoogle() {
      const clientId = "144092227095-2o4leroklngkidvlum4s8vlguchcc4av.apps.googleusercontent.com";
      const provider = "google";
      const redirectUri = "http://localhost:5173/login";
      const scope = "openid email profile";
      const responseType = "code";

      const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${clientId}&redirect_uri=${encodeURIComponent(redirectUri)}&response_type=${responseType}&state=${provider}&scope=${encodeURIComponent(scope)}`;

      window.location.href = authUrl;  
    },

    loginWithFacebook() {
      const clientId = "2642256836111553";
      const provider = "facebook";
      const redirectUri = "http://localhost:5173/login";
      const scope = "public_profile,pages_manage_posts,pages_show_list,business_management";


      const facebookAuthUrl = `https://www.facebook.com/v21.0/dialog/oauth?client_id=${clientId}&redirect_uri=${encodeURIComponent(redirectUri)}&scope=${scope}&state=${provider}&response_type=code`;
      window.location.href = facebookAuthUrl;
    },
    
    // Methods that handles Forgot Password, Verify OTP, and Reset Password
    sendPasswordResetOtp() {
      if (!this.gmailForgotPassword.trim()) {
        this.usernameError = "Email is required";
        return;
      }

      this.loading = true;
      this.requestError = "";
      const csrfToken = document.querySelector("meta[name='csrf-token']")?.content || "";

      if (!csrfToken) {
        this.requestError = "CSRF token missing. Please refresh the page.";
        this.loading = false;
        return;
      }

      $.ajax({
        url: `${this.$store.getters["api_base"]}?e=auth&a=send-reset-otp`,
        type: "POST",
        xhrFields: { withCredentials: true },
        data: {
          email: this.gmailForgotPassword,
        },
        headers: { "X-CSRF-Token": csrfToken },
        success: (data) => {
          console.log("OTP sent successfully:", data);
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error("Error:", textStatus, errorThrown);
          let errorMsg = "An error occurred while processing your request.";
          if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
            errorMsg = jqXHR.responseJSON.message;
          } else if (jqXHR.responseText) {
            errorMsg = jqXHR.responseText;
          }
          this.requestError = errorMsg;
        },
        complete: () => {
          this.loading = false;
          this.sendOtp = false;
          this.verifyOtp = true;
        },
      });
    },

    verifyResetOtp() {
      this.loading = true;
      const csrfToken = document.querySelector("meta[name='csrf-token']")?.content || "";
      if (!csrfToken) {
        this.requestError = "CSRF token missing. Please refresh the page.";
        this.loading = false;
        return;
      }

      $.ajax({
        url: `${this.$store.getters["api_base"]}?e=auth&a=verify-reset-otp`,
        type: "POST",
        xhrFields: { withCredentials: true },
        data: {
          email: this.gmailForgotPassword,
          otp: this.otp,
        },
        headers: { "X-CSRF-Token": csrfToken },
        success: (data) => {
          console.log(data);
          this.resetToken = data.data.reset_token;
          this.verifyOtp = false;
          this.resetPassword = true; // show new password form
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error("Error:", textStatus, errorThrown);
          let errorMsg = "An error occurred while processing your request.";
          if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
            errorMsg = jqXHR.responseJSON.message;
          } else if (jqXHR.responseText) {
            errorMsg = jqXHR.responseText;
          }
          this.requestError = errorMsg;
        },
        complete: () => {
          this.loading = false;
        },
      });

    },

    createNewPassword() {
      this.loading = true;
      const csrfToken = document.querySelector("meta[name='csrf-token']")?.content || "";
      if (!csrfToken) {
        this.requestError = "CSRF token missing. Please refresh the page.";
        this.loading = false;
        return;
      }

      $.ajax({
        url: `${this.$store.getters["api_base"]}?e=auth&a=reset-password`,
        type: "POST",
        xhrFields: { withCredentials: true },
        data: {
          email: this.gmailForgotPassword,
          new_password: this.newPassword,
        },
        headers: { 
          "X-CSRF-Token": csrfToken,
          "Authorization": `Bearer ${this.resetToken}`, 
        },
        success: (data) => {
          console.log(data);
          this.$store.commit("auth/setUser", data.data);
          const barangaySlug = data?.data?.barangay?.slug;
          if (!barangaySlug) {
            this.requestError = "Invalid response from server. Missing barangay slug.";
            return;
          }
          this.$router.replace({
            name: "admin-dashboard",
            params: { barangaySlug },
          });
        },
        error: (jqXHR, textStatus, errorThrown) => {
          console.error("Error:", textStatus, errorThrown);
          let errorMsg = "An error occurred while processing your request.";
          if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
            errorMsg = jqXHR.responseJSON.message;
          } else if (jqXHR.responseText) {
            errorMsg = jqXHR.responseText;
          }
          this.requestError = errorMsg;
        },
        complete: () => {
          this.loading = false;
        },
      });
    }

  },
  computed: {
  passwordMismatch() {
    return this.newPassword && this.reEnterPassword && this.newPassword !== this.reEnterPassword;
  }
}

};
</script>

<template>
  <v-container
    fluid
    class="login-container"
    :style="{ backgroundImage }"
  >
    <v-card 
    v-if="!forgotPassword"
    class="login-card" elevation="20">
      <!-- Logo -->
      <div class="logo-container">
        <v-img src="/public/Flogo.svg" max-width="300" class="mx-auto mb-3" />
      </div>

      <!-- Form -->
      <v-form @submit.prevent="handleSubmit">
        <v-text-field
          v-model="username"
          label="Username"
          prepend-inner-icon="mdi-account"
          variant="outlined"
          density="comfortable"
          :error-messages="usernameError"
          :disabled="loading"
        />
        <v-text-field
          v-model="password"
          label="Password"
          type="password"
          prepend-inner-icon="mdi-lock"
          variant="outlined"
          density="comfortable"
          :error-messages="passwordError"
          :disabled="loading"
        />

        <v-btn size="large" block color="primary" type="submit" :loading="loading">
          Log In
        </v-btn>

        <h4
        @click="forgotPassword = !forgotPassword"
        class="text-center text-blue-500 pt-3">Forgot Password?</h4>

        <v-card 
        class="reqErr pa-5 my-3 rounded-2xl" 
        v-if="requestError">
          {{ requestError }}
        </v-card>
      </v-form>

      <!-- OAuth buttons -->
      <div class="h-auto d-flex flex-col ga-2 py-5">
        <v-btn size="large" @click="loginWithFacebook">
          <div class="d-flex justify-center items-center ga-3">
            <v-avatar size="25" :image="$store.getters['base'] + 'public/fb.png'" />
            <span class="text-sm">Continue with Facebook</span>
          </div>
        </v-btn>

        <v-btn size="large" @click="loginWithGoogle">
          <div class="d-flex justify-center items-center ga-3">
            <v-avatar size="30" :image="$store.getters['base'] + 'public/google_logo.svg'" />
            <span class="text-sm">Continue with Google</span>
          </div>
        </v-btn>
      </div>
    </v-card>

    <v-card 
    v-if="forgotPassword"
    class="forgot-password-card d-flex flex-col justify-center items-center">
      <!-- Logo -->
      <div class="logo-container">
        <v-img src="/public/Flogo.svg" width="300"/>
      </div>

      <!-- Email and Send OTP Card -->
      <div v-if="sendOtp" class="w-full d-flex flex-col ga-3 justify-center items-center">
        <!-- Title Section -->
        <v-card-title class="w-full d-flex align-center justify-center ga-5 border-b py-5">
            <v-icon size="25">mdi-key-change</v-icon>
            <h2 class="font-extrabold text-xl">Forgot Password</h2>
            <v-icon size="25">mdi-key-change</v-icon>
        </v-card-title>

        <v-text-field
        class="w-[90%]"
        v-model="gmailForgotPassword"
        label="Email"
        prepend-inner-icon="mdi-account"
        variant="outlined"
        density="comfortable"
        :error-messages="usernameError"
        :disabled="loading"
        hide-details="auto"
        />
   
        <!-- Action Buttons: Save/Discard -->
        <v-card-actions 
        class="w-full d-flex justify-center items-center gap-10 pt-5 border-t"
        style="position: relative; bottom: 0;">
            <v-btn 
            @click="sendPasswordResetOtp()"
            color="teal-lighten-1">Send Code</v-btn>
        </v-card-actions>
      </div>

      <!-- Verify OTP -->
      <div v-if="verifyOtp" class="w-full d-flex flex-col ga-3 justify-center items-center">
        <!-- Title Section -->
        <v-card-title class="w-full d-flex align-center justify-center ga-5 border-b py-5">
            <v-icon size="25">mdi-key-change</v-icon>
            <h2 class="font-extrabold text-xl">Verify OTP</h2>
            <v-icon size="25">mdi-key-change</v-icon>
        </v-card-title>

        <v-otp-input 
        class="w-[90%]"
        v-model="otp"/>

        <!-- Action Buttons: Save/Discard -->
        <v-card-actions 
        class="w-full d-flex justify-center items-center gap-10 pt-5 border-t"
        style="position: relative; bottom: 0;">
            <v-btn 
            @click="verifyResetOtp()"
            color="teal-lighten-1">Verify</v-btn>
        </v-card-actions>
      </div>

      <!-- New Password Form -->
      <div v-if="resetPassword" class="w-full d-flex flex-col ga-3 justify-center items-center">
        <!-- Title Section -->
        <v-card-title class="w-full d-flex align-center justify-center ga-5 border-b py-5">
            <v-icon size="25">mdi-key-change</v-icon>
            <h2 class="font-extrabold text-xl">Reset Password</h2>
            <v-icon size="25">mdi-key-change</v-icon>
        </v-card-title>

          <v-text-field
            class="w-[90%]"
            v-model="newPassword"
            label="New Password"
            type="password"
            variant="outlined"
          />

          <v-text-field
            class="w-[90%]"
            v-model="reEnterPassword"
            label="Re-enter New Password"
            type="password"
            variant="outlined"
            :error="passwordMismatch"
            :error-messages="passwordMismatch ? 'Passwords do not match' : ''"
          />

          <v-card-actions>
            <v-btn
              color="teal-lighten-1"
              :disabled="!newPassword || !reEnterPassword || passwordMismatch"
              @click="createNewPassword()"
            >
              Save Password
            </v-btn>
          </v-card-actions>


      </div>
    </v-card>
  </v-container>
</template>


<style scoped>
.login-container {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-position: center;
    background-attachment: fixed;
    gap: 1rem;
}

.login-card {
    width: 90%;
    width: 500px;
    border-radius: 1rem;
    padding: 3rem 2rem;
    background: rgba(255, 255, 255, 0);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
}

.forgot-password-card {
    width: 90%;
    width: 500px;
    border-radius: 1rem;
    padding: 3rem 2rem;
    background: rgba(255, 255, 255, 0);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
    margin-bottom: 1rem;
}

.logo-container {
    display: flex;
    justify-content: center;
}

.primary {
    cursor: pointer;
    color: #fff;
    text-decoration: underline;
}

.privacy-toggle:hover {
    opacity: 0.8;
}

.reqErr {
    width: 100%;
    display: flex;
    justify-content: center;
    color: rgb(245, 49, 49);
    margin-bottom: 1rem;
    font-size: 120%;
    border: red .5px solid;
    
}

.v-text-field .v-icon {
    color: var(--v-theme-on-surface);
}

.v-text-field .v-field-label {
    color: var(--v-theme-on-surface) !important;
}

.dialog {
    padding: 1rem;
}
</style>
