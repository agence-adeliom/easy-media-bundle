import MediaModal from '../components/utils/external-modal.vue'

export default {
    components: {MediaModal},
    data() {
        return {
            inputName: ''
        }
    },
    methods: {
        toggleModalFor(name) {
            this.inputName = name
            EventHub.fire('modal-show', this.$el)
        },
        hideInputModal() {
            this.inputName = ''
            EventHub.fire('modal-hide')
        }
    }
}
