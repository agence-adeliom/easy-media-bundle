<template>
    <span v-if="currentType.type">
        <icon :class="classes"
              :name="currentType.icon"
              :scale="iconScale"/>
    </span>
</template>

<script>
export default {
    props: [
        'file',
        'scale',
        'classes',
        'fileTypeIs',
        'except'
    ],
    data() {
        return {
            iconScale : this.scale || 1.2,
            exclude   : this.except || [],
            list      : [
                {
                    type : 'folder',
                    icon : 'folder'
                },
                {
                  type : 'oembed',
                  icon : 'external-link-alt'
                },
                {
                  type : 'application',
                  icon : 'cogs'
                },
                {
                    type : 'compressed',
                    icon : 'regular/file-archive'
                },
                {
                    type : 'image',
                    icon : 'image'
                },
                {
                    type : 'video',
                    icon : 'film'
                },
                {
                    type : 'audio',
                    icon : 'music'
                },
                {
                  type : 'text',
                  icon : 'regular/file-alt'
                },
                {
                  type : 'pdf',
                  icon : 'regular/file-pdf'
                }
            ]
        }
    },
    computed: {
        typesList() {
            return this.list.filter((item) => {
                return !this.exclude.some((e) => e == item.type)
            })
        },
        currentType() {
            let file = this.file

            return this.typesList.find((e) => this.fileTypeIs(file, e.type)) || {}
        }
    }
}
</script>
