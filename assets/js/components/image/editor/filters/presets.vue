<template>
    <div>
        <h3 :style="{'opacity': processing ? 0.5 : 1}">
            {{ trans('presets') }}
        </h3>

        <section>
            <div v-for="(chunk, i) in chunkedItems"
                 :key="i"
                 class="col">
                <button type="button" v-for="item in chunk"
                        :key="item"
                        v-tippy="{zIndex: 999999999, arrow: true, theme: 'mm'}"
                        :disabled="processing"
                        :class="{'is-active': isUsed(item)}"
                        :title="getTitle(item)"
                        class="btn-plain"
                        @click.stop="apply(item)">
                    <span v-show="processing"
                          class="icon is-small">
                        <icon :pulse="processing"
                              name="spinner"/>
                    </span>
                    <span v-show="!processing">{{ truncate(item) }}</span>
                </button>
            </div>
        </section>
    </div>
</template>

<script>
import chunk from 'lodash/chunk'
import camelCase from 'lodash/camelCase'
import snakeCase from 'lodash/snakeCase'

export default {
    props: [
        'processing',
        'trans',
        'camanFilters',
        'applyFilter'
    ],
    data() {
        return {
            presets: [
                'Clarity',
                'Pinhole',
                'Love',
                'Jarques',
                'Orange Peel',
                'Sin City',
                'Grungy',
                'Old Boot',
                'Lomo',
                'Vintage',
                'Cross Process',
                'Concentrate',
                'Glowing Sun',
                'Sunrise',
                'Nostalgia',
                'Hemingway',
                'Her Majesty',
                'Hazy Days'
            ]
        }
    },
    computed: {
        chunkedItems () {
            return chunk(this.presets, 9)
        }
    },
    methods: {
        truncate(str) {
            return str.match(/\b(\w)/g).join('')
        },
        isUsed(name) {
            return this.camanFilters.hasOwnProperty(camelCase(name))
        },
        apply(name) {
            return this.applyFilter(camelCase(name), null)
        },
        getTitle(str) {
            return this.trans(snakeCase(str))
        }
    }
}
</script>
