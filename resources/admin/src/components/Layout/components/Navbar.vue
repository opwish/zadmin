<template>
  <div class="header">
    <a-button @click.stop="$store.dispatch('toggleOpened')">
      <a-icon :type="collapsed ? 'menu-unfold' : 'menu-fold'" :style="{'fontSize':'18px','margin-top':'3px'}"/>
    </a-button>

    <breadcrumb class="pb-0"/>
    <div class="flex-spacer"/>
    <refresh/>
    <a-dropdown
      :trigger="['click']"
      placement="bottomRight"
      class="mr-2"
    >
      <a class="ant-dropdown-link" @click="e => e.preventDefault()" style="color:rgba(0, 0, 0, 0.65);">
        <img
          v-if="avatar"
          class="avatar"
          :src="avatar"
          :alt="user.name"
          :title="user.name"
        >
        <span v-else>{{ user.name }}</span>
        <a-icon type="down"/>
      </a>
      <a-menu slot="overlay">
        <a-menu-item>
          <router-link :to="{ name: 'editMyProfile' }">个人资料</router-link>
        </a-menu-item>
        <a-menu-divider/>
        <a-menu-item @click="onLogout">退出登录</a-menu-item>
      </a-menu>
    </a-dropdown>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import { getUrl } from '@/libs/utils'
import Refresh from '@c/Refresh'
import Breadcrumb from './Breadcrumb'

export default {
  name: 'Navbar',
  components: { Refresh, Breadcrumb},
  computed: {
    ...mapState({
      miniWidth: (state) => state.miniWidth,
      collapsed: (state) => !state.sideMenu.opened,
      user: (state) => state.users.user,
    }),
    avatar() {
      return this.user.avatar && getUrl(this.user.avatar)
    },
  },
  methods: {
    onLogout() {
      this.$store.dispatch('logout')
    },
  },
}
</script>

<style scoped lang="less">
@import "~@/styles/vars";

button {
  // line-height: 64px;
  // height: 100%;
  // border: none;
  // border-radius: 0;
  // padding: 0 24px;
  // font-size: 16px;
  border:none;
}

button[ant-click-animating-without-extra-node]:after {
 border: 0 none;
 opacity: 0;
 animation:none 0 ease 0 1 normal;
}

.header {
  display: flex;
  background: #fff;
  align-items: center;
  border-bottom: 1px solid #d3d5db;
  box-shadow: 0 2px 4px rgba(0, 0, 0, .12), 0 0 6px rgba(0, 0, 0, .04)
}

.avatar {
  border-radius: @border-radius-base;
  max-width: 40px;
  max-height: 40px;
}
</style>
