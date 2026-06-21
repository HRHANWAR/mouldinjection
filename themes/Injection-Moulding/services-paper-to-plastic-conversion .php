<?php
/**
 * Template Name: Service - Paper to Plastic Conversion 
 * Template Post Type: page
 */
get_header();
?>

<main class="im-page-main">
	<?php
// Featured image URL uthane ke liye
$hero_bg = get_the_post_thumbnail_url(get_the_ID(), 'full');
// Agar image nahi hai toh default image lagane ke liye
if (!$hero_bg) {
    $hero_bg = get_template_directory_uri() . '/assets/images/Services/Injection-Moulding.png';
}
?>
<script>
// Scroll Animation Logic
    window.addEventListener('scroll', () => {
      const line = document.getElementById('line-progress');
      const rows = document.querySelectorAll('.timeline-row');
      const dots = document.querySelectorAll('.dot');
      const innerDots = document.querySelectorAll('.inner-dot');

      const scrollPos = window.scrollY + (window.innerHeight * 0.5);
      const section = line.closest('section');
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;

      // Line Progress Calculation
      let progress = ((scrollPos - sectionTop) / sectionHeight) * 100;
      line.style.height = Math.min(Math.max(progress, 0), 100) + '%';

      // Activate Dots & Glowing Core
      rows.forEach((row, i) => {
        const rowTop = row.getBoundingClientRect().top + window.scrollY;
        if (scrollPos > rowTop) {
          // Active Glow State
          dots[i].classList.add('border-[#C8FF00]', 'shadow-[0_0_20px_rgba(200,255,0,0.6)]');
          innerDots[i].classList.replace('bg-white/10', 'bg-[#C8FF00]');
          innerDots[i].classList.add('shadow-[0_0_10px_#C8FF00]');
        } else {
          // Inactive State
          dots[i].classList.remove('border-[#C8FF00]', 'shadow-[0_0_20px_rgba(200,255,0,0.6)]');
          innerDots[i].classList.replace('bg-[#C8FF00]', 'bg-white/10');
          innerDots[i].classList.remove('shadow-[0_0_10px_#C8FF00]');
        }
      });
    });
</script>
<section class="relative min-h-[265px] w-full flex flex-col items-center bg-center bg-cover"
    style="background-image: url('<?php echo esc_url( get_template_directory_uri() . '/assets/images/SmallMediumBatchRuns/header.png' ); ?>');"

    <!-- Aapka Navbar (Header) yahan rahega jo aapne diya hai -->
    <!-- Note: Navbar ko 'fixed' ki jagah 'absolute' rakhein agar sirf is section mein chahiye -->
    
    <div class="absolute bottom-10 left-0 w-full px-6 lg:px-20 font-regular font-[Helvetica] text-[20px]">
      <div class="max-w-7xl mx-auto flex items-center gap-2 text-white font-medium">
        <a href="<?php echo home_url(); ?>" class="hover:underline">Home</a>
        <span class="text-white">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" stroke-width="2"></path>
          </svg>
        </span>
        <a href="#" class="hover:underline">Services</a>
        <span class="text-white mx-1">/</span>
        <span class="text-[#C8FF00] hover:underline"><?php the_title(); ?></span>
      </div>
    </div>
</section>
<!-- ========= INTRO SECTION ========= -->
<section class="reveal py-16 bg-[#F5F6FA]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="flex flex-col lg:flex-row gap-12 items-center">
 
      <!-- Left -->
      <div class="w-full lg:w-1/2">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#D7FF80]/50 rounded-full mb-6">
          <span class="w-2 h-2 rounded-full bg-[#19191A]"></span>
          <span class="text-[15px] font-medium font-[Poppins] text-[#19191A]">Paper to Plastic Conversion</span>
        </div>
        <h1 class="text-[32px] lg:text-[40px] font-bold font-[Helvetica] text-[#19191A] leading-tight mb-6">
          Redesign Paper Products<br>Into Durable Plastic Alternatives
        </h1>
        <p class="text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed mb-4">
          Many paper and cardboard products can now be successfully converted into reusable, moisture-resistant, stronger plastic alternatives.
        </p>
        <p class="text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed">
          We help businesses redesign existing products and connect them with suitable manufacturers.
        </p>
      </div>
 
      <!-- Right: Image -->
      <div class="w-full lg:w-1/2">
        <div class="rounded-2xl overflow-hidden shadow-md">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/paper-to-plastic.png"
               alt="Paper to Plastic Conversion"
               class="w-full h-[300px] object-cover rounded-2xl">
        </div>
      </div>
 
    </div>
  </div>
</section>
 
<!-- ========= WHAT IS IT SECTION ========= -->
<section class="reveal py-20 bg-[#142F32]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="flex flex-col lg:flex-row gap-16 items-center">
 
      <!-- Left -->
      <div class="w-full lg:w-1/2">
        <!-- Badge -->
        <div class="flex mb-5">
          <div class="relative">
            <div class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]"></div>
            <div class="flex items-center gap-2 px-4 py-1.5 bg-[#051616]/50 border border-white/10 rounded-2xl backdrop-blur-sm">
              <span class="w-1.5 h-1.5 bg-white rounded-full"></span>
              <span class="text-white text-[14px] font-[Poppins] font-light">What Is It?</span>
            </div>
          </div>
        </div>
 
        <h2 class="font-[Helvetica] text-[32px] lg:text-[40px] text-[#FFFFFF] font-bold leading-tight mb-6">
          Paper to <span class="text-[#C8FF00]">Plastic Conversion</span>
        </h2>
        <p class="text-[#C8CCD9] text-[15px] font-[Montserrat] leading-relaxed mb-4">
          Paper to plastic conversion is the process of redesigning an existing paper, cardboard, pulp, or disposable product into a moulded plastic component using modern manufacturing methods.
        </p>
        <p class="text-[#C8CCD9] text-[15px] font-[Montserrat] leading-relaxed">
          This can reduce breakages, improve hygiene, increase lifespan, and lower long-term costs.
        </p>
      </div>
 
      <!-- Right: Manufacturing Methods card -->
      <div class="w-full lg:w-1/2">
        <div class="bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] border border-white/10 rounded-3xl p-5">
 
          <!-- Header row with icon -->
          <div class="flex items-center gap-4 mb-4">
            <div class="w-11 h-11 bg-[#C8FF00]/15 border border-[#C8FF00]/40 rounded-xl flex items-center justify-center flex-shrink-0">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/methode.png"
               alt="Paper to Plastic Conversion"
               class="w-[18px] h-[18px] object-cover rounded-2xl">
        
            </div>
            <h3 class="text-[#EBEDF0] font-bold text-[20px] font-[Helvetica]">Manufacturing Methods</h3>
          </div>
 
          <!-- Items — each is its own separate card -->
          <div class="space-y-2">
            <?php
            $methods = [
              '01' => 'Injection Moulding',
              '01' => 'Blow Moulding',
              '01' => 'Thermoforming',
              '01' => 'Recycled Plastic Manufacturing',
            ];
            $methods_list = [
              ['num' => '01', 'label' => 'Injection Moulding'],
              ['num' => '01', 'label' => 'Blow Moulding'],
              ['num' => '01', 'label' => 'Thermoforming'],
              ['num' => '01', 'label' => 'Recycled Plastic Manufacturing'],
            ];
            foreach ($methods_list as $m) : ?>
            <div class="flex items-center gap-4 bg-white/5 rounded-xl px-5 py-3.5">
              <span class="flex-shrink-0 w-8 h-8 rounded-full border border-white/20 flex items-center justify-center text-[#8A9BB0] text-[11px] font-semibold font-[Poppins]"><?php echo $m['num']; ?></span>
              <span class="text-[#EBEDF0] text-[16px] font-medium font-[Montserrat]"><?php echo $m['label']; ?></span>
            </div>
            <?php endforeach; ?>
          </div>
 
        </div>
      </div>
 
    </div>
  </div>
</section>
 
<!-- ========= PRODUCTS COMMONLY CONVERTED ========= -->
<section class="reveal py-20 bg-[#142F32]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
 
    <div class="text-center mb-14">
      <p class="text-[#C8FF00] font-medium text-[16px] font-[Helvetica] mb-2">Products Commonly Converted</p>
      <h2 class="font-bold text-[36px] lg:text-[44px] font-[Helvetica] text-[#FFFFFF]">
        From Paper <span class="text-[#C8FF00]">To Plastic</span>
      </h2>
    </div>
 
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
 
      <?php
     $categories = [
    [
        'title' => 'Packaging Products',
        'image' => get_template_directory_uri() . '/assets/images/Packaging.png',
        'items' => ['Trays', 'Inserts', 'Dividers', 'Retail packaging'],
    ],
    [
        'title' => 'Industrial Products',
        'image' => get_template_directory_uri() . '/assets/images/industrial-products.png',
        'items' => ['Transit trays', 'Protective inserts', 'Storage components'],
    ],
    [
        'title' => 'Consumer Products',
        'image' => get_template_directory_uri() . '/assets/images/consumer.png',
        'items' => ['Containers', 'Lids', 'Dispensers', 'Reusable packaging'],
    ],
    [
        'title' => 'Medical / Hygiene',
        'image' => get_template_directory_uri() . '/assets/images/medical.png',
        'items' => ['Dose trays', 'Protective covers', 'Disposable housings'],
    ],
];
      foreach ($categories as $cat) : ?>
      <div class="bg-[#143E44] border border-white/10 rounded-2xl p-6">
        <div class="w-10 h-10 bg-[#1a3d40] border border-[#C8FF00]/20 rounded-lg flex items-center justify-center mb-5">
    <img
        src="<?php echo esc_url($cat['image']); ?>"
        alt="<?php echo esc_attr($cat['title']); ?>"
        class="w-6 h-6 object-contain"
    >
</div>
        <h3 class="text-[#EBEDF0] font-semibold text-[16px] font-[Helvetica] mb-4"><?php echo $cat['title']; ?></h3>
        <ul class="space-y-2">
          <?php foreach ($cat['items'] as $item) : ?>
          <li class="flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-[#C8FF00] flex-shrink-0"></span>
            <span class="text-[#8A9BB0] text-[14px] font-[Montserrat]"><?php echo $item; ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
 
    </div>
  </div>
</section>
 
<!-- ========= BENEFITS SECTION ========= -->
<section class="reveal py-20 bg-[#142F32]">
  <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
 
    <p class="text-[#C8FF00] font-medium text-[18px] font-[Helvetica] mb-3">Benefits</p>
    <h2 class="text-[32px] md:text-[40px] font-bold font-[Helvetica] text-[#FFFFFF] mb-14">
      Why Move From <span class="text-[#C8FF00]">Paper To Plastic</span>
    </h2>
 
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
 
      <!-- Card 1 -->
      <div class="group bg-[#143E44] border border-white/10 rounded-2xl p-6 text-left cursor-pointer transition-all duration-300 hover:bg-white hover:border-transparent hover:shadow-2xl">
        <div class="w-12 h-12 rounded-xl bg-[#FBFBFB]/5 group-hover:bg-[#eef1f5] flex items-center justify-center mb-6 transition-colors duration-300">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Stronger & Longer Lasting.png" alt="Stronger & Longer Lasting" class="  h-[28px] w-[28px] ransition-all duration-300 group-hover:brightness-0" />
        </div>
        <h3 class="text-[#EBEDF0] group-hover:text-[#19191A] text-[20px] font-bold font-[Helvetica] mb-3 transition-colors duration-300">Stronger & Longer Lasting</h3>
        <p class="text-[#8A9BB0] group-hover:text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed transition-colors duration-300">Plastic offers improved durability and moisture resistance.</p>
      </div>
 
      <!-- Card 2 -->
      <div class="group bg-[#143E44] border border-white/10 rounded-2xl p-6 text-left cursor-pointer transition-all duration-300 hover:bg-white hover:border-transparent hover:shadow-2xl">
        <div class="w-12 h-12 rounded-xl bg-[#FBFBFB]/5 group-hover:bg-[#eef1f5] flex items-center justify-center mb-6 transition-colors duration-300">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Reusable Options.png" alt="Reusable Options" class="  h-[28px] w-[28px] ransition-all duration-300 group-hover:brightness-0" />
        </div>
        <h3 class="text-[#EBEDF0] group-hover:text-[#19191A] text-[20px] font-bold font-[Helvetica] mb-3 transition-colors duration-300">Reusable Options</h3>
        <p class="text-[#8A9BB0] group-hover:text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed transition-colors duration-300">Many products can be reused multiple times.</p>
      </div>
 
      <!-- Card 3 -->
      <div class="group bg-[#143E44] border border-white/10 rounded-2xl p-6 text-left cursor-pointer transition-all duration-300 hover:bg-white hover:border-transparent hover:shadow-2xl">
        <div class="w-12 h-12 rounded-xl bg-[#FBFBFB]/5 group-hover:bg-[#eef1f5] flex items-center justify-center mb-6 transition-colors duration-300">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Better Appearance.png" alt="Better Appearance" class="  h-[28px] w-[28px] ransition-all duration-300 group-hover:brightness-0" />
        </div>
        <h3 class="text-[#EBEDF0] group-hover:text-[#19191A] text-[20px] font-bold font-[Helvetica] mb-3 transition-colors duration-300">Better Appearance</h3>
        <p class="text-[#8A9BB0] group-hover:text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed transition-colors duration-300">Premium finish, colour control, branding, texture.</p>
      </div>
 
      <!-- Card 4 -->
      <div class="group bg-[#143E44] border border-white/10 rounded-2xl p-6 text-left cursor-pointer transition-all duration-300 hover:bg-white hover:border-transparent hover:shadow-2xl">
        <div class="w-12 h-12 rounded-xl bg-[#FBFBFB]/5 group-hover:bg-[#eef1f5] flex items-center justify-center mb-6 transition-colors duration-300">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Hygienic.png" alt="Hygienic" class="  h-[28px] w-[28px] ransition-all duration-300 group-hover:brightness-0" />
        </div>
        <h3 class="text-[#EBEDF0] group-hover:text-[#19191A] text-[20px] font-bold font-[Helvetica] mb-3 transition-colors duration-300">Waterproof / Hygienic</h3>
        <p class="text-[#8A9BB0] group-hover:text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed transition-colors duration-300">Suitable for wet environments and clean applications.</p>
      </div>
 
      <!-- Card 5 -->
      <div class="group bg-[#143E44] border border-white/10 rounded-2xl p-6 text-left cursor-pointer transition-all duration-300 hover:bg-white hover:border-transparent hover:shadow-2xl">
        <div class="w-12 h-12 rounded-xl bg-[#FBFBFB]/5 group-hover:bg-[#eef1f5] flex items-center justify-center mb-6 transition-colors duration-300">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/High Volume Production.png" alt="High Volume Production" class="  h-[28px] w-[28px] ransition-all duration-300 group-hover:brightness-0" />
        </div>
        <h3 class="text-[#EBEDF0] group-hover:text-[#19191A] text-[20px] font-bold font-[Helvetica] mb-3 transition-colors duration-300">High Volume Production</h3>
        <p class="text-[#8A9BB0] group-hover:text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed transition-colors duration-300">Fast cycle manufacturing once tooling is complete.</p>
      </div>
 
      <!-- Card 6 -->
      <div class="group bg-[#143E44] border border-white/10 rounded-2xl p-6 text-left cursor-pointer transition-all duration-300 hover:bg-white hover:border-transparent hover:shadow-2xl">
        <div class="w-12 h-12 rounded-xl bg-[#FBFBFB]/5 group-hover:bg-[#eef1f5] flex items-center justify-center mb-6 transition-colors duration-300">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Lower Long-Term Cost.png" alt="Lower Long-Term Cost" class="  h-[28px] w-[16px] ransition-all duration-300 group-hover:brightness-0" />
        </div>
        <h3 class="text-[#EBEDF0] group-hover:text-[#19191A] text-[20px] font-bold font-[Helvetica] mb-3 transition-colors duration-300">Lower Long-Term Cost</h3>
        <p class="text-[#8A9BB0] group-hover:text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed transition-colors duration-300">Especially for repeat-use products.</p>
      </div>
 
    </div>
  </div>
</section>
 
 
<!-- ========= MATERIAL OPTIONS ========= -->
<section class="reveal py-20 bg-[#142F32]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10 text-center">
 
    <p class="text-[#C8FF00] font-medium text-[16px] font-[Helvetica] mb-2">Material Options</p>
    <h2 class="font-bold text-[36px] lg:text-[40px] font-[Helvetica] text-[#FFFFFF] mb-12">
      We Can Help <span class="text-[#C8FF00]">Source</span>
    </h2>
 
    <!-- Row 1: 3 cols -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4 max-w-7xl mx-auto">
      <?php foreach (['PP (Polypropylene)', 'HDPE', 'ABS'] as $mat) : ?>
      <div class="bg-[#143E44] border border-white/10 rounded-xl px-6 py-4 text-center" style="border-left: 3px solid #C8FF00;">
        <span class="text-[#EBEDF0] text-[16px] font-semibold font-[Montserrat]"><?php echo $mat; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <!-- Row 2: 3 cols -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-7xl mx-auto">
      <?php foreach (['Recycled Plastics', 'Bioplastics (where suitable)', 'ABPETS'] as $mat) : ?>
      <div class="bg-[#143E44] border border-white/10 rounded-xl px-6 py-4 text-center" style="border-left: 3px solid #C8FF00;">
        <span class="text-[#EBEDF0] text-[16px] font-semibold font-[Montserrat]"><?php echo $mat; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
 
  </div>
</section>
 
  <!-- ================= Moulding Process start================= -->
  <section class="relative py-20 overflow-hidden bg-[#00191C]">
    <div class="reveal text-center mb-32 px-6">
      <p class="text-[#C8FF00] font-medium text-[20px] tracking-wide font-[Helvetica] mb-4">Our Process</p>
      <h2 class="font-medium text-[40px] font-[Helvetica] text-[#FFFFFF] mb-2">
        From Concept To <span class="text-[#C8FF00] "> Production</span>
      </h2>
     
    </div>

    <div class="reveal max-w-6xl mx-auto relative px-6">

      <div
        class="hidden lg:block absolute left-1/2 -translate-x-1/2 top-0 h-full w-[14px] bg-glassBg/30 border border-white/5 rounded-full overflow-hidden shadow-[inset_0_0_10px_rgba(0,0,0,0.5)]">
        <div id="line-progress"
          class="w-full h-0 bg-[#C8FF00]/30 rounded-full transition-all duration-500 ease-out relative shadow-[0_0_20px_rgba(200,255,0,0.6)]">
          <div
            class="absolute left-1/2 -translate-x-1/2 w-[2px] h-full bg-[#C8FF00] shadow-[0_0_15px_#C8FF00,0_0_5px_#fff]">
          </div>
        </div>
      </div>

      <div class="space-y-24 lg:space-y-1">

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pr-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->


              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Send Existing Product.png" alt="Mould Clamp" class=" shadow-md" />
                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 1</p>
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Send Existing Product
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                Provide photos, dimensions, or samples.
              </p>
            </div>
          </div>

          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="hidden lg:block w-1/2"></div>
        </div>

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="hidden lg:block w-1/2"></div>
          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pl-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15),rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->

              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Services/icon/EjectionFinishedPart.png" alt="Ejection Finished Part"
                    class=" shadow-md" />
                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 2</p>
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Design Review
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
               We assess whether the paper product can be converted.
              </p>
            </div>
          </div>
        </div>

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pr-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->
              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Manufacturing Proposal.png" alt="Injection" class=" shadow-md" />
                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 3</p>
              </div>

              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
               Manufacturing Proposal
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
               Tooling, unit costs, materials, lead times.
              </p>
            </div>
          </div>
          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="hidden lg:block w-1/2"></div>
        </div>

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="hidden lg:block w-1/2"></div>
          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pl-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->
              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Sampling & Production.png" alt="Cooling / Solidifying"
                    class=" shadow-md" />

                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 4</p>
              </div>



              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
               Sampling & Production
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
               Prototype approval then production.
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
<!-- ========= IDEAL FOR SECTION ========= -->
<section class="reveal py-20 bg-[#F5F6FA]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10 text-center">
 
    <!-- Badge -->
    <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#D7FF80]/50 rounded-full mb-5">
      <span class="w-2 h-2 rounded-full bg-[#19191A]"></span>
      <span class="text-[15px] font-medium font-[Poppins] text-[#19191A]">Ideal For Companies Looking To</span>
    </div>
 
    <!-- Heading -->
    <h2 class="font-bold text-[32px] lg:text-[40px] font-[Helvetica] text-[#19191A] mb-12">
      Modernise Your Product Range
    </h2>
 
    <!-- Items grid: 2 cols -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-left">
      <?php
      $ideals = [
        'Improve product durability',
        'Replace weak paper packaging',
        'Waterproof current products',
        'Launch reusable alternatives',
        'Improve hygiene',
        'Scale production volumes',
        'Modernise product range',
      ];
      foreach ($ideals as $item) : ?>
      <div class="bg-white rounded-2xl px-6 py-5 shadow-sm" style="border-left: 4px solid #C8FF00;">
        <span class="text-[#19191A] text-[16px] font-medium font-[Montserrat]"><?php echo $item; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
 
  </div>
</section>
 
<!-- ========= FAQ SECTION ========= -->
<section class="w-full bg-[#142F32] py-16 px-6 md:px-12 lg:px-24 font-[Poppins]">
 
  <div class="reveal max-w-4xl mx-auto text-center pb-8">
    <p class="text-[#C8FF00] font-medium font-[Helvetica] text-[20px] uppercase tracking-widest">FAQ</p>
    <h2 class="text-white text-[24px] md:text-[40px] font-medium font-[Helvetica]">
      Frequently Asked <span class="text-[#C8FF00]">Questions</span>
    </h2>
  </div>
 
  <?php
  $faqs = [
    ['num' => '01', 'q' => 'What types of paper products can be converted?', 'a' => 'Many trays, sleeves, inserts, holders, packaging items, containers and custom products can be converted to plastic.'],
    ['num' => '02', 'q' => 'Will plastic be more expensive?',                 'a' => 'Initial tooling costs apply, but for repeat-use or volume products, plastic typically offers lower long-term unit costs.'],
    ['num' => '03', 'q' => 'How long does the process take?',                  'a' => 'Timelines vary by project complexity. We will provide estimated lead times as part of the manufacturing proposal.'],
    ['num' => '04', 'q' => 'Can plastic look premium?',                        'a' => 'Yes — plastic moulding allows for high-quality surface finishes, colours, textures, and branding options.'],
    ['num' => '05', 'q' => 'Can you help with design?',                        'a' => 'Yes, we can assist with design review and connect you with suitable manufacturers who can support your project from concept to production.'],
  ];
  foreach ($faqs as $faq) : ?>
  <div class="reveal max-w-7xl mx-auto space-y-4 mb-4">
    <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
      <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
        <div class="flex items-center gap-4 md:gap-6">
          <span class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white font-bold font-[Poppins] text-[16px] border border-white/10 shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
            <?php echo $faq['num']; ?>
          </span>
          <h3 class="text-white text-[16px] md:text-[22px] font-[Helvetica] leading-tight"><?php echo $faq['q']; ?></h3>
        </div>
        <div class="flex-shrink-0 ml-4">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
            class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 opacity-60 group-hover:opacity-100"
            style="transform: rotate(0deg);">
        </div>
      </button>
      <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24 transition-all duration-300">
        <p class="text-[#F0F1F5] text-sm md:text-[18px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
          <?php echo $faq['a']; ?>
        </p>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
 
</section>
  <!-- ================= Join Us Now section end ================= -->

  <section class="w-full bg-[#f4f7f6] px-6 py-20 lg:px-20 lg:py-24">
    <div
      class="reveal max-w-7xl mx-auto rounded-[24px] bg-[#153F45] text-white p-10 flex flex-col items-center justify-center text-center shadow-2xl shadow-[#153F45]/10">

      <div class="relative group">
        <div
          class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]">
        </div>

        <div class="flex items-center gap-3 px-6 py-2  border border-white/10 rounded-[10px] backdrop-blur-sm">
          <span class="w-2 h-2 bg-white rounded-full"></span>

          <span class="text-white font-regular text-[18px] font-[Poppins] font-light tracking-wide">
            Join Us Now
          </span>
        </div>
      </div>

      <h2
        class="text-[32px] md:text-[32px] lg:text-[32px] font-bold font-[Poppins] tracking-wide leading-tight  mt-3">
        Ready To
        <span class="text-[#C8FF00]"> Convert</span>?
      </h2>

      <p class="text-[16px] md:text-[16px] text-slate-200 font-[Poppins] font-medium max-w-3xl mb-6 mt-3">
       Send us your existing paper product and we'll assess the best plastic conversion route.
      </p>

      <a href="/contact-us"
        class="inline-flex items-center gap-3 rounded-full border border-white px-6 py-2 text-[12px] md:text-[18px] lg:text-[18px] font-medium font-[Montserrat] text-white transition-all  active:scale-95">
        Start Your Conversion
        <span class="text-xl leading-none  transition-transform"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png" alt=""
            class="w-[20px] h-[20px]"></span>
      </a>

    </div>
  </section>
  <!-- ================= Join Us Now section end ================= -->

</main>
<?php get_footer(); ?>



