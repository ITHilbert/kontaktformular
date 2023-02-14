<template>
    <form method="POST" v-bind:action="route" enctype="multipart/form-data">
        <input type="hidden" name="_token" v-bind:value="csrf" />
        <label class="font-weight-bold">Name *</label>
        <input type="text" class="form-control" name="Name" placeholder="Namen eingeben" required /><br>
        <label class="font-weight-bold">E-Mail *</label>
        <input type="mail" class="form-control" name="Email" placeholder="E-Mail eingeben" required /><br>
        <label class="font-weight-bold">Telefonnummer *</label>
        <input type="text" class="form-control" name="Telefon" placeholder="Telefonnummer eingeben"  required /><br>
        <label class="font-weight-bold">Ihre Nachricht *</label>
        <textarea class="form-control" name="Nachricht" placeholder="Hier Ihre Nachricht eingeben ..." required ></textarea><br>
        <label class="font-weight-bold" for="fileUpload">Datei Anhängen (optional)</label><br>
        <input type="file"  name="Datei" /><br>

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
    }
}
</script>

<style>

</style>
