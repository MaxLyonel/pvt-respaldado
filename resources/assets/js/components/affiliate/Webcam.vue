<template>
<div class="container" style="margin-left:10px">
    <div class="row">
    <div class="col-md-6">
        <h2>FOTOGRAFIA</h2>
        <div class="border">
        <vue-web-cam ref="webcam" style="border:5px solid #ddd"
            :device-id="deviceId"
            width="90%"
            height="90%"
            @started="onStarted"
            @stopped="onStopped"
            @error="onError"
            @cameras="onCameras"
            @camera-change="onCameraChange" />
        </div>

        <div class="row">
        <div class="col-md-12">
            <select v-model="camera">
            <option>-- Select Device --</option>
            <option v-for="device in devices"
            :key="device.deviceId"
            :value="device.deviceId">{{ device.label }}</option>
            </select>
        </div>
        <div class="col-md-12">
            <v-btn
                value="picture"
                color="primary"
                @click.stop="onCapture()"
            >CAPTURAR FOTO</v-btn>
            <v-btn
                color="error"
                @click.stop="onStop()"
            >STOP CAMARA</v-btn>

            <v-btn
                color="accent"
                @click="onStart()"
            >HABILITAR CAMARA</v-btn>

        </div>
        </div>
    </div>
    <div class="col-md-6">
        <h2>IMAGEN CAPTURADA</h2>
        <figure class="figure">
        <img :src="img" class="img-responsive" width="95%" height="95%" style="border:5px solid #ddd">
        </figure>
        <v-btn @click="savePicture()"
                :disabled="false"
            color="primary"
        >GUARDAR FOTOGRAFIA</v-btn>
    </div>
    </div>
</div>
</template>

<script>
import { WebCam } from "vue-web-cam";
export default {
name: "cam",
components: {
"vue-web-cam": WebCam
},
data() {
    return {
    img: null,
    camera: null,
    deviceId: null,
    event: null,
    devices: []
    };
},
computed: {
    device: function() {
    return this.devices.find(n => n.deviceId === this.deviceId);
    }
},
watch: {
    camera: function(id) {
    this.deviceId = id;
    },
    devices: function() {
      // Once we have a list select the first one
    const [first, ...tail] = this.devices;
    if (first) {
        this.camera = first.deviceId;
        this.deviceId = first.deviceId;
    }
    }
},
/*beforeMount(){
   this.getAffiliate();
},*/
mounted(){
    this.getAffiliate(this.$route.params.id)
},
methods: {
    onCapture() {
    this.img = this.$refs.webcam.capture();
    },
    onStarted(stream) {
    console.log("On Started Event", stream);
    },
    onStopped(stream) {
    console.log("On Stopped Event", stream);
    },
    onStop() {
    this.$refs.webcam.stop();
    },
    onStart() {
    this.$refs.webcam.start();
    },
    onError(error) {
    console.log("On Error Event", error);
    },
    onCameras(cameras) {
    this.devices = cameras;
    console.log("On Cameras Event", cameras);
    },
    onCameraChange(deviceId) {
    this.deviceId = deviceId;
    this.camera = deviceId;
    console.log("On Camera Change Event", deviceId);
    },
    async savePicture() {
    try {
        this.img = this.$refs.webcam.capture();
        this.$route.params.id
        console.log(this.img)
        //let res = await axios.patch(`affiliate/${this.affiliate.id}`, this.img)
        //console.log(res)
        let rest = await axios.patch(`affiliate/${this.affiliate.id}/profile_picture`, {
        'image': this.img
        })
        console.log(rest)
        this.toastr.success('Fotografias Adicionada')
    } catch (e) {
    console.log(e)
    } finally {
    this.loading = false
    }
    },

    async getAffiliate(id) {
    try {
        this.loading = true
        let res = await axios.get(`affiliate_show/${id}`)
        this.affiliate = res.data
    } catch (e) {
        console.log(e)
    } finally {
        this.loading = false
    }
    }
}
};
</script>
