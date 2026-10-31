<template>
    <form method="POST" v-bind:action="route" enctype="multipart/form-data" v-on:submit.prevent="absenden" ref="formular">
        <input type="hidden" name="_token" v-bind:value="csrf" ref="token" />
        <input type="hidden" name="site" v-bind:value="currentUrl" />

        <input type="text" name="fax" class="n-bot" tabindex="-1" autocomplete="off">
        <label for="contact_name" class="font-weight-bold">Name *</label>
        <input type="text" class="form-control" id="contact_name" name="Name" placeholder="Namen eingeben" autocomplete="name" required /><br>

        <label for="contact_email" class="font-weight-bold">E-Mail *</label>
        <input type="email" class="form-control" id="contact_email" name="Email" placeholder="E-Mail eingeben" autocomplete="email" required /><br>

        <label for="contact_telefon" class="font-weight-bold">Telefonnummer</label>
        <input type="tel" class="form-control" id="contact_telefon" name="Telefon" placeholder="Telefonnummer eingeben (optional)" autocomplete="tel" /><br>

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
import { erzeugeTokenAuffrischer } from './tokenAuffrischen.mjs';

export default {
    props: {
        'route': '',
        'tokenRoute': {
            type: String,
            default: '/kontaktformular/csrf-token',
        },
    },
    data: function(){
        return {
            csrf: "",
        }
    },
    mounted() {
        this.csrf = document.querySelector('meta[name="csrf-token"]').content;
        window.addEventListener('pageshow', this.beiPageshow);
    },
    beforeDestroy() {
        window.removeEventListener('pageshow', this.beiPageshow);
    },
    methods: {
        beiPageshow(event) {
            // Seite kommt per „Zurück“ aus dem Browser-Cache: Absenden wieder freigeben.
            if (event.persisted && this.absender) {
                this.absender.zuruecksetzen();
            }
        },
        absenden() {
            // Natives submit() löst kein erneutes submit-Event aus; das Feld wird direkt im DOM gesetzt,
            // v-bind:value="csrf" überschreibt es nicht, solange csrf unverändert bleibt.
            if (!this.absender) {
                this.absender = erzeugeTokenAuffrischer({
                    formular: this.$refs.formular,
                    feld: this.$refs.token,
                    url: this.tokenRoute,
                });
            }
            return this.absender.absenden();
        },
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
.n-bot {
    position: absolute !important;
    left: -10000px !important;
    top: auto !important;
    width: 1px !important;
    height: 1px !important;
    overflow: hidden !important;
}
</style>
