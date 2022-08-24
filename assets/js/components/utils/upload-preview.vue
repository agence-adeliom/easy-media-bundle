<template>
  <section class="preview-container"
           style="--width: 45%;">
    <!-- preview -->
    <div v-if="img">
      <img :src="img">
    </div>

    <div v-else
         class="icons-preview">
      <icon-types :file="type"
                  :file-type-is="fileTypeIs"
                  :scale="10"
                  :except="['image']"/>
    </div>

    <!-- options -->
    <div v-if="img"
         class="options btn-animate"
         :class="{'show': panelIsVisible}">
      <button type="button" v-tippy="{zIndex: 999999999, arrow: true, placement: 'left'}"
              class="btn-plain"
              :class="{'alt': panelIsVisible}"
              :title="trans('options')"
              @click.stop="switchPanel()">
                <span class="icon is-large">
                    <icon>
                        <icon name="circle"
                              scale="2.5"/>
                        <icon :name="!panelIsVisible ? 'cog' : 'times'"
                              class="icon-btn"/>
                    </icon>
                </span>
      </button>

      <!-- panel -->
      <div :class="{'show': panelIsVisible}"
           class="panel">

        <!-- title -->
        <section>
          <label class="input-label" >
            {{ trans('seo.title') }}
          </label>
          <input v-model="options.title"
                 class="input">
        </section>
        <!-- alt -->
        <section>
          <label class="input-label" >
            {{ trans('seo.alt') }}
          </label>
          <input v-model="options.alt"
                 class="input">
        </section>

        <!-- desc -->
        <section>
          <label class="input-label" >
            {{ trans('seo.description') }}
          </label>
          <textarea v-model="options.description"
                    rows="10"
                    class="textarea"/>
        </section>
      </div>
    </div>

    <!-- info -->
    <div v-show="!panelIsVisible"
         class="info">
      {{ name }}
    </div>
  </section>
</template>

<style lang="scss">
img {
  @apply block;
}

.preview-container {
  @apply h-full w-full relative overflow-x-hidden overflow-y-scroll;

  .icons-preview {
    @apply h-full w-full items-center justify-center flex;
  }

  .info {
    @apply sticky bottom-0 text-white transition-all px-4 py-2 left-0;
    background: linear-gradient(45deg, black, transparent);
    @screen sm{
      left: -2px;
    }

    &:hover {
      @apply opacity-0;
    }

    &:empty {
      @apply p-0;
    }
  }

  .options {
    @apply h-full absolute top-0 transition-all items-start hidden;
    width: var(--width);
    @screen sm{
      @apply flex;
    }
    &.show {
      @apply right-0 #{!important};
      @apply z-3;
    }

    .btn-plain {
      @apply pr-4 pt-4 z-2;
      &.alt {
        @apply text-black opacity-100 #{!important};
        .icon-btn {
          @apply text-white #{!important};
        }
      }
    }

    .panel {
      @apply backdrop-blur bg-theme-5 rounded-none flex flex-col h-full w-full overflow-scroll p-4 gap-6;
    }

    .dimensions,
    .focals,
    .extras {
      @apply mb-4;
      .data-container {
        @apply flex w-full;
      }

      .field {
        @apply w-1/2;
        &:first-of-type {
          @apply mr-3;
        }
      }
    }

    .extras {
      .level {
        @apply m-0;
      }

      .data-container {
        @apply mb-2;
        &:last-of-type {
          @apply m-0;
        }
      }

      .field {
        @apply m-0 #{!important};
        @apply w-full;
      }

      .arr {
        @apply mt-4;
        &:empty {
          @apply mb-0;
        }
      }
    }

    textarea,
    input {
      @apply shadow-none opacity-50 transition-all;
      &:focus {
        @apply opacity-80;
      }
    }

    h3 {
      @apply mb-1;
    }
  }
}



</style>

<script>
import cloneDeep from 'lodash/cloneDeep'

export default {
  props: [
    'file',
    'fileTypeIs',
    'trans'
  ],
  data() {
    return {
      img: this.file.dataURL || null,
      type: this.file.type,
      name: this.file.name,
      panelIsVisible: false,

      options: {
        alt: null,
        title: null,
        description: null,
        extra: []
      }
    }
  },
  activated() {
    this.updateParentPanel()
    this.addSpaceToOptBtn()
  },
  methods: {
    updateParentPanel() {
      this.$parent.uploadPreviewOptionsPanelIsVisible = this.panelIsVisible
    },
    addSpaceToOptBtn() {
      let cont = document.querySelector('.options')

      if (cont) {
        let btn = cont.querySelector('.btn-plain')
        cont.style.right = `calc((var(--width) * -1) + ${btn.offsetWidth}px)`
      }
    },
    switchPanel() {
      return this.panelIsVisible = !this.panelIsVisible
    }
  },
  watch: {
    options: {
      deep: true,
      handler(val) {
        let list = this.$parent.uploadPreviewOptionsList
        let data = cloneDeep(val)
        data.extra = data.extra.filter((item) => item.name || item.data) || []

        let index = list.findIndex((e) => e.name == this.name)
        index < 0
            ? list.push({
              name: this.name,
              options: data
            })
            : list[index].options = data
      }
    },
    panelIsVisible(val) {
      this.updateParentPanel()
    }
  }
}
</script>
