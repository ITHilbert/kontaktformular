<template>
    <form method="POST" v-bind:action="route" enctype="multipart/form-data">
        <input type="hidden" name="_token" v-bind:value="csrf" />
        <input type="hidden" name="site" v-bind:value="currentUrl" />

        <input type="text" name="website" style="display:none !important" tabindex="-1" autocomplete="off">
        <label for="contact_name" class="font-weight-bold">Name *</label>
        <input type="text" class="form-control" id="contact_name" name="Name" placeholder="Namen eingeben" autocomplete="name" required /><br>

        <label for="contact_email" class="font-weight-bold">E-Mail *</label>
        <input type="email" class="form-control" id="contact_email" name="Email" placeholder="E-Mail eingeben" autocomplete="email" required /><br>

        <label for="contact_telefon" class="font-weight-bold">Telefonnummer *</label>
        <input type="text" class="form-control" id="contact_telefon" name="Telefon" placeholder="Telefonnummer eingeben" autocomplete="tel"  required /><br>

        <label for="contact_nachricht" class="font-weight-bold">Ihre Nachricht *</label>
        <textarea rows="4" class="form-control" name="Nachricht" id="contact_nachricht" placeholder="Hier Ihre Nachricht eingeben ..." autocomplete="off" required ></textarea><br>

        <label for="contact_file_upload" class="font-weight-bold">Datei Anhängen (optional)<br>
        <input type="file" name="Datei" id="contact_file_upload" /></label><br>

        <label class="font-weight-bold" for="contact_datenverarbeitung">
            <input type="checkbox" name="datenverarbeitung" id="contact_datenverarbeitung" value="1" autocomplete="off" required>
            Ich stimme der Verarbeitung meiner Daten zu (<a href="https://www.it-hilbert.com/datenschutz">Datenschutz-Informationen)</a>*
        </label><br>

        <button type="submit" class="btn btn-primary">Anfrage senden</button>
    </form>
</template>


<script>
export default {
    props: {
        'route': '',
    },
    data: function(){
        return {
            csrf: "",
        }
    },
    mounted() {
        this.csrf = document.querySelector('meta[name="csrf-token"]').content;
    },
    computed: {
        currentUrl() {
            return window.location.href;
            // or: return this.$route.path;
            // or, with mode: 'history': return this.$route.fullPath;
        }
    }
}
</script>

<style>

</style>
