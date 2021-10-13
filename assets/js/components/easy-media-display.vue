<script>
import MediaModal from '../mixins/modal'
import Utilities      from '../modules/utils'
import Gestures       from '../modules/gestures'
import Image       from '../modules/image'

require('../packages/download.min')

// axios
window.axios                  = require('axios').default
axios.defaults.headers.common = {
  'X-Requested-With' : 'XMLHttpRequest'
}
axios.interceptors.response.use(
    (response) => response,
    (error) => Promise.reject(error.response)
)

export default {
  name: 'easy-media-display',
  components: {
    imagePreview         : require('./image/preview.vue').default,
    imageIntersect       : require('./image/lazyLoading.vue').default,
  },
  mixins: [MediaModal, Utilities, Gestures, Image],
  props: [
    'config',
    'routes',
    'media'
  ],
  // the inputs we want to use the manager for
  // in our case they are the article 'cover' & 'gallery'
  // for multi-selected files 'links' "issues/40"
  //
  // for usage with an editor "wysiwyg" only, you dont need this part
  data() {
    return {
      activeModal : null,
      selectedFile: null,
      dimensions: []
    }
  },
  beforeMount() {
    this.eventsListener();
  },
  mounted() {
    if(this.media){
      this.getInfos(this.media);
    }
  },
  methods: {
    saveFile(item) { downloadFile(item.path)},
    eventsListener() {
      // get images dimensions
      EventHub.listen('save-image-dimensions', (obj) => {
        if (!this.checkForDimensions(obj.url)) {
          this.dimensions.push(obj)
        }
      })
    },
    getInfos(path){
      return axios.post(this.routes.file_infos, {
        path: path
      }).then(({data}) => {
        this.selectedFile = data
      }).catch((err) => {
        console.error(err)
      })
    },
    selectedFileIs(val) {
      let selected = this.selectedFile

      if (selected) {
        return this.fileTypeIs(selected, val)
      }
    },
    fileTypeIs(item, val) {
      let mimes = this.config.mimeTypes
      let type = item.type || item
      console.log(type);
      if (type) {
        if (val == 'image' && mimes.image.includes(type)) {
          return true
        }

        // because "pdf" shows up as "application"
        if ((type && type.includes('pdf')) && val != 'pdf') {
          return false
        }

        // because "archive" shows up as "application"
        if ((type && type.includes('compressed')) || mimes.archive.includes(type)) {
          return val == 'compressed' ? true : false
        }

        return type && type.includes(val)
      }
    },
  }
}
</script>
