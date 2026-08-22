<?php get_header(); ?>
<div id="saro-main" class="flex min-h-[70vh] items-center justify-center bg-cream px-4">
    <div class="text-center">
        <p class="font-naskh text-[7rem] font-bold leading-none text-gold-hair md:text-[11rem]">۴۰۴</p>
        <h1 class="mt-2 font-naskh text-2xl font-bold text-teal md:text-3xl">صفحه‌ای پیدا نشد!</h1>
        <p class="mt-3 text-sm text-muted-foreground">صفحه‌ای که دنبالش می‌گردید حذف شده یا آدرسش تغییر کرده.</p>
        <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <a href="<?php echo esc_url( home_url('/') ); ?>"
                class="saro-btn">
                بازگشت به خانه
            </a>
            <a href="<?php echo esc_url( wc_get_page_permalink('shop') ); ?>"
                class="saro-btn-ghost">
                مشاهده فروشگاه
            </a>
        </div>
        <form role="search" method="get" action="<?php echo esc_url( home_url('/') ); ?>"
            class="mx-auto mt-10 flex max-w-sm items-center gap-2 rounded-full border border-gold-line bg-card px-4 py-1.5">
            <input type="search" name="s" placeholder="جست‌وجو در سایت..."
                class="min-w-0 flex-1 border-0 bg-transparent px-1 py-2 font-sans text-sm text-ink outline-none placeholder:text-muted-foreground" />
            <input type="hidden" name="post_type" value="product" />
            <button type="submit" class="saro-btn shrink-0 rounded-full py-2 text-xs">جست‌وجو</button>
        </form>
    </div>
</div>
<?php get_footer(); ?>
