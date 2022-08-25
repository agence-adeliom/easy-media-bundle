<template>
    <div class="wrapper">
        <div data-img-container>
            <slot/>
        </div>

        <div class="goo">
            <div v-if="showOps"
                 class="circular-menu"
                 :class="{'active' : opsMenu}">
                <div class="floating-btn"
                     @click="toggleOpsMenu()">
                    <span class="icon is-large"><icon name="cog"/></span>
                </div>

                <menu class="items-wrapper">
                    <!-- move -->
                    <div class="menu-item" v-if="!inMovableList() && root.enableMove">
                        <button type="button" class="button btn-plain"
                                :disabled="ops_btn_disable"
                                @click.stop="addToMovableList()">
                            <span class="icon is-large">
                                <icon v-if="inMovableList()"
                                      name="minus"
                                      scale="1.2"/>
                                <icon v-else
                                      name="plus"
                                      scale="1.2"/>
                            </span>
                        </button>
                    </div>

                    <!-- edit metas -->
                    <div class="menu-item" v-if="root.enableMetas">
                      <button type="button" class="button btn-plain"
                              :disabled="ops_btn_disable"
                              @click.stop="editMetasItem()">
                              <span class="icon is-large">
                                  <icon name="hashtag"
                                        scale="1.2"/>
                              </span>
                      </button>
                    </div>

                    <!-- rename -->
                    <div class="menu-item" v-if="root.enableRename">
                        <button type="button" class="button btn-plain"
                                :disabled="ops_btn_disable"
                                @click.stop="renameItem()">
                            <span class="icon is-large">
                                <icon name="i-cursor"
                                      scale="1.2"/>
                            </span>
                        </button>
                    </div>

                    <!-- editor -->
                    <div class="menu-item" v-if="root.enableEditor">
                        <button type="button" class="button btn-plain"
                                :disabled="ops_btn_disable"
                                @click.stop="imageEditorCard()">
                            <span class="icon is-large">
                                <icon name="regular/object-ungroup"
                                      scale="1.2"/>
                            </span>
                        </button>
                    </div>

                    <!-- delete -->
                    <div class="menu-item bg-danger" v-if="root.enableDelete">
                        <button type="button" class="button btn-plain"
                                :disabled="ops_btn_disable"
                                @click.stop="deleteItem()">
                            <span class="icon is-large">
                                <icon name="regular/trash-alt"
                                      scale="1.2"/>
                            </span>
                        </button>
                    </div>
                </menu>
            </div>
        </div>
    </div>
</template>

<script>

export default {
    props: [
        'trans',
        'showOps',
        'ops_btn_disable',
        'inMovableList',
        'renameItem',
        'editMetasItem',
        'deleteItem',
        'imageEditorCard',
        'addToMovableList'
    ],
    data() {
        return {
            opsMenu: false,
            root: this.$root.$children[0]
        }
    },
    methods: {
        getContainer(el) {
            return el.querySelector('[data-img-container]')
        },
        toggleOpsMenu() {
            return this.opsMenu = !this.opsMenu
        }
    }
}
</script>

<style lang="scss" scoped>
//@import '../../../sass/modules/scroll-btn';
@import '../../../sass/packages/goo';

.wrapper {
  @apply overflow-hidden relative block;
  min-height: auto;
    > div:first-child {
      @apply block;
        padding-bottom: 62.5%;
        @screen sm {
          width: 60vh;
        }
    }
}

</style>
