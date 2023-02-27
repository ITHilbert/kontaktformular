<template>
    <form method="POST" v-bind:action="route" enctype="multipart/form-data">
        <input type="hidden" name="_token" v-bind:value="csrf" />
        <input type="hidden" name="site" v-bind:value="currentUrl" />
        <label class="font-weight-bold">Name *
            <input type="text" class="form-control" name="Name" placeholder="Namen eingeben" required />
        </label><br>
        <label class="font-weight-bold">E-Mail *
            <input type="email" class="form-control" name="Email" placeholder="E-Mail eingeben" required />
        </label><br>
        <label class="font-weight-bold">Telefonnummer *
            <input type="text" class="form-control" name="Telefon" placeholder="Telefonnummer eingeben"  required />
        </label><br>
        <label class="font-weight-bold">Ihre Nachricht *
            <textarea class="form-control" name="Nachricht" placeholder="Hier Ihre Nachricht eingeben ..." required ></textarea>
        </label><br>
        <label class="font-weight-bold" for="fileUpload">Datei Anhängen (optional)<br>
            <input type="file"  name="Datei" />
        </label><br>

        <label class="font-weight-bold">
            <input type="checkbox" name="Datenverarbeitung" class="form-control" required>
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
