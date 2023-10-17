<!--
  - Copyright (c) 2021-Present Enalean
  -
  - Permission is hereby granted, free of charge, to any person obtaining a copy
  - of this software and associated documentation files (the "Software"), to deal
  - in the Software without restriction, including without limitation the rights
  - to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  - copies of the Software, and to permit persons to whom the Software is
  - furnished to do so, subject to the following conditions:
  -
  - The above copyright notice and this permission notice shall be included in all
  - copies or substantial portions of the Software.
  -
  - THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  - IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  - FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  - AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  - LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  - OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  - SOFTWARE.
  -->

<template>
    <a
        v-if="config.instance_information.logo.legacy_png_href !== null"
        id="logo"
        ref="legacy_logo"
        v-bind:href="homepage_link"
        v-bind:aria-label="config.internationalization.homepage"
        data-test="legacy-logo"
    ></a>
    <a
        v-else-if="config.instance_information.logo.svg !== null"
        v-bind:href="homepage_link"
        class="logo-svg-link"
        v-bind:aria-label="config.internationalization.homepage"
    >
        <span
            v-dompurify-html="config.instance_information.logo.svg.normal"
            class="logo-svg-normal"
            data-test="custom-normal-logo"
        ></span>
        <span
            v-dompurify-html="config.instance_information.logo.svg.small"
            class="logo-svg-small"
            data-test="custom-small-logo"
        ></span>
    </a>
    <a
        v-else
        v-bind:href="homepage_link"
        class="logo-svg-link"
        v-bind:aria-label="config.internationalization.homepage"
    >
        <span class="logo-svg-normal" data-test="default-normal-logo">
            <svg
                width="110"
                height="32"
                viewBox="0 0 110 32"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M13.5211 12.224C12.9663 12.7308 11.1287 14.3402 11.1294 14.3396C10.3614 15.0233 9.85746 15.511 9.50468 15.9334C9.48408 15.9232 6.51508 14.327 6.27482 14.1744C6.48536 13.6764 6.76798 13.0179 6.97011 12.5848C7.33268 11.8078 8.01686 10.616 8.01686 10.616C8.01686 10.616 7.51116 11.1863 6.73422 10.1503C5.95727 9.11441 5.10506 7.63831 4.06913 7.63831C3.03321 7.63831 2.01094 10.616 2.01094 10.616C2.01094 10.616 1.68689 10.6683 3.03359 12.3776C4.2922 13.975 6.37956 15.7051 6.56843 15.8007C6.80716 15.941 8.38299 16.8701 8.51381 16.9356C7.2558 18.8463 7.48383 20.3386 7.48383 22.1952C7.48383 22.6101 8.08794 24.4626 8.31681 24.9321C8.52923 25.3678 8.76878 25.8066 9.06712 26.277C9.101 26.3304 10.1247 27.3243 10.2049 27.4274C8.5048 27.2705 7.2397 26.4183 6.24496 25.7928C-0.592148 21.4937 -1.5112 13.672 2.01094 7.40463C4.30405 3.32424 8.31681 10.1503 8.31681 10.1503C8.31681 10.1503 9.97429 7.45692 13.4446 4.14196C13.7401 4.59154 13.9626 5.12609 14.2872 5.78078C15.737 1.60344 16.2937 0.750816 17.2258 0.723402C19.8005 0.647673 23.9593 9.1662 21.8874 16.9356C20.4978 22.1469 14.8419 27.0533 11.4153 27.4274C11.1294 27.0827 10.8924 26.7918 10.7864 26.6495C10.4832 26.2426 10.4873 26.0499 10.2049 25.6045C9.92007 25.1555 9.70422 24.7962 9.50468 24.3868C9.2963 23.9594 9.15294 23.427 9.06712 23.0838C8.54375 20.9903 8.5315 19.2975 9.89761 17.5348C10.2185 17.1208 10.9249 16.2597 11.7588 15.5174C11.7531 15.5224 13.7257 13.6852 14.2872 13.1724C16.7585 10.9152 18.3066 9.31438 18.8293 7.44767C19.5482 5.22288 19.9377 5.03591 18.8293 3.41281C18.8293 3.41281 17.8991 2.39076 17.2258 2.44256C16.5524 2.49436 16.067 4.39326 15.9634 4.65224C15.8598 4.91122 15.5196 5.48816 14.7945 7.40463C14.9086 7.66131 15.0847 7.46196 15.4647 8.38926C15.6412 8.82003 15.8157 9.31273 15.9634 9.73455C15.3145 10.5079 14.5023 11.3278 13.5211 12.224Z"
                    class="logo-svg-fill-theme-color"
                />
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M34.785 5.39548V8.46621H32.4482V11.5369H34.785V19.1406C34.7788 21.3432 35.1841 22.8535 36.0994 23.8198C36.8729 24.6324 38.1227 25.1359 39.6046 25.1359C40.9599 25.1359 42.0746 24.9345 42.6716 24.6972V21.6265C42.1421 21.7124 41.7368 21.7459 41.0651 21.7727C39.4062 21.7459 38.8658 20.739 38.8743 18.702V11.5369H42.8177V8.46621H38.8743V4.22568L34.785 5.39548ZM57.2765 8.61257H55.6699V18.5559C55.6746 19.2194 55.5401 19.8531 55.3778 20.3106C54.6661 21.9545 52.9518 23.5889 50.5582 23.5275C47.4054 23.5889 46.3298 21.0873 46.3228 17.6785V8.61257H44.7163V17.971C44.7163 23.9558 48.1786 24.9898 50.2661 24.9898C53.0863 24.9898 54.935 23.2553 55.6699 21.7728H55.816L55.962 24.5511H57.4225C57.3217 23.3887 57.2881 22.1213 57.2765 20.7492V8.61257ZM61.3659 24.6972H62.9724V0.862549H61.3659V24.6972ZM79.0377 16.3623C79.0377 16.1285 79.0377 15.8936 79.0377 15.485C79.0377 13.1413 78.0266 8.17373 72.7576 8.17373C68.5559 8.17373 65.4552 11.5302 65.4552 16.9472C65.4552 21.6331 68.3537 24.9896 73.0497 24.9896C75.8359 24.9896 77.4873 24.2847 78.1614 23.966L77.7233 22.65C76.7796 23.1436 75.5662 23.6135 73.1958 23.6736C69.8704 23.6135 67.0393 21.566 67.0617 16.3623H79.0377ZM67.2077 15.0465C67.5114 12.599 69.0296 9.48995 72.6115 9.48995C76.5193 9.48995 77.464 12.8305 77.4311 15.0465H67.2077ZM91.7439 14.4614C91.7981 11.6575 90.8802 8.17373 86.1941 8.17373C84.6933 8.17373 82.8576 8.5757 81.3745 9.48976L81.8126 10.6596C83.0616 9.88212 84.5573 9.51364 86.048 9.48976C89.8604 9.48014 90.1663 12.6624 90.1374 14.169V14.6076C83.5035 14.6053 80.2061 16.8496 80.2061 20.6029C80.2061 22.7118 81.7018 24.9896 85.0257 24.9896C87.6168 24.9896 89.4185 23.5492 90.2834 22.3576V22.3576L90.5755 24.5509H92.036C91.832 23.3817 91.7981 22.0753 91.7439 20.7491V14.4614ZM90.1374 19.5792C90.1374 19.7772 90.1037 20.1435 89.9914 20.4566C89.4306 22.0417 87.8824 23.6735 85.1718 23.6735C83.5069 23.6735 81.9587 22.6412 81.9587 20.3104C81.9587 16.5135 86.6034 15.9807 90.1374 16.0698V19.5792ZM95.3952 31.2773H97.0017V22.0651H97.1478C98.1993 23.9673 100.268 25.0068 102.552 24.9896C106.541 25.0068 110 22.056 110 16.3623C110 11.594 107.185 8.17373 102.99 8.17373C100.166 8.17373 98.1654 9.48148 97.0017 11.5369H96.8557V8.61241H95.2491C95.3509 10.0851 95.3848 11.6611 95.3952 13.7303V31.2773ZM97.0017 15.1927C97.0017 14.6996 97.1377 14.1955 97.2938 13.7305C97.9538 11.1033 100.266 9.48995 102.698 9.48995C106.353 9.48995 108.393 12.7502 108.393 16.3625C108.393 20.6824 106.251 23.6738 102.552 23.6738C99.9941 23.6738 97.8178 22.0604 97.1477 19.5795C97.0357 19.1699 97.0017 18.733 97.0017 18.2634V15.1927Z"
                    class="logo-svg-fill-default-text-color"
                />
            </svg>
        </span>
        <span class="logo-svg-small" data-test="default-small-logo">
            <svg
                width="24"
                height="32"
                viewBox="0 0 24 32"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M14.2975 12.1488C13.7427 12.6556 11.905 14.265 11.9058 14.2644C11.1378 14.9481 10.6338 15.4358 10.2811 15.8582C10.2605 15.848 7.29145 14.2518 7.05119 14.0992C7.26174 13.6012 7.54436 12.9427 7.74649 12.5096C8.10906 11.7326 8.79324 10.5408 8.79324 10.5408C8.79324 10.5408 8.28754 11.1111 7.51059 10.0751C6.73365 9.03921 5.88144 7.56311 4.84551 7.56311C3.80958 7.56311 2.78732 10.5408 2.78732 10.5408C2.78732 10.5408 2.46326 10.5931 3.80997 12.3024C5.06858 13.8998 7.15594 15.6299 7.3448 15.7255C7.58354 15.8658 9.15936 16.795 9.29019 16.8605C8.03218 18.7711 8.26021 20.2634 8.26021 22.12C8.26021 22.5349 8.86431 24.3874 9.09319 24.8569C9.30561 25.2926 9.54516 25.7314 9.8435 26.2018C9.87737 26.2552 10.9011 27.2491 10.9812 27.3522C9.28117 27.1953 8.01607 26.3431 7.02134 25.7176C0.184227 21.4185 -0.73483 13.5968 2.78732 7.32943C5.08043 3.24904 9.09319 10.0751 9.09319 10.0751C9.09319 10.0751 10.7507 7.38173 14.221 4.06676C14.5165 4.51634 14.739 5.05089 15.0636 5.70559C16.5133 1.52824 17.07 0.675621 18.0021 0.648206C20.5769 0.572478 24.7357 9.09101 22.6638 16.8605C21.2741 22.0717 15.6183 26.9781 12.1916 27.3522C11.9058 27.0075 11.6688 26.7166 11.5628 26.5743C11.2596 26.1674 11.2637 25.9747 10.9812 25.5293C10.6964 25.0803 10.4806 24.721 10.2811 24.3116C10.0727 23.8842 9.92932 23.3518 9.8435 23.0086C9.32013 20.9151 9.30787 19.2223 10.674 17.4596C10.9949 17.0456 11.7013 16.1845 12.5351 15.4422C12.5295 15.4472 14.5021 13.61 15.0636 13.0972C17.5349 10.84 19.083 9.23918 19.6057 7.37247C20.3246 5.14768 20.7141 4.96072 19.6057 3.33761C19.6057 3.33761 18.6755 2.31557 18.0021 2.36737C17.3288 2.41916 16.8433 4.31806 16.7397 4.57704C16.6361 4.83603 16.296 5.41297 15.5708 7.32943C15.6849 7.58612 15.8611 7.38676 16.2411 8.31406C16.4176 8.74483 16.5921 9.23753 16.7397 9.65935C16.0909 10.4327 15.2787 11.2526 14.2975 12.1488Z"
                    class="logo-svg-fill-theme-color"
                />
            </svg>
        </span>
    </a>
</template>
<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { sanitizeURL } from "./url-sanitizer";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SIDEBAR_CONFIGURATION } from "./injection-symbols";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";

const config = strictInject(SIDEBAR_CONFIGURATION);
const homepage_link = computed(() => sanitizeURL(config.instance_information.logo.logo_link_href));
const vDompurifyHtml = buildVueDompurifyHTMLDirective({
    default: {
        USE_PROFILES: {
            svg: true,
        },
    },
});
const legacy_logo = ref<InstanceType<typeof HTMLElement>>();
const updateLegacyLogoURLCssVar = (addresses: { normal: string; small: string } | null): void => {
    legacy_logo.value?.style.setProperty(
        "--logo-url",
        `url(${sanitizeURL(addresses?.normal ?? "")})`,
    );
    legacy_logo.value?.style.setProperty(
        "--logo-small-url",
        `url(${sanitizeURL(addresses?.small ?? "")})`,
    );
};
onMounted(() => {
    watch(
        (): { normal: string; small: string } | null =>
            config.instance_information.logo.legacy_png_href,
        updateLegacyLogoURLCssVar,
    );
    updateLegacyLogoURLCssVar(config.instance_information.logo.legacy_png_href);
});
</script>
