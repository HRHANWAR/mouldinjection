<?php
/**
 * Template Name: Service - Glass to Plastic
 * Template Post Type: page
 */
get_header();
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
<main class="im-page-main">
	<?php
// Featured image URL uthane ke liye
$hero_bg = get_the_post_thumbnail_url(get_the_ID(), 'full');
// Agar image nahi hai toh default image lagane ke liye
if (!$hero_bg) {
    $hero_bg = get_template_directory_uri() . '/assets/images/Services/Injection-Moulding.png';
}
?>

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


 <!-- ========= Glass to Plastic Conversion start ======================= -->
  <section class="reveal py-20 bg-[#F5F6FA]">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex flex-col lg:flex-row items-center gap-16">

        <div class="reveal  mx-auto">
          <div class="reveal inline-flex items-center gap-2 px-3 py-1 bg-[#D7FF80]/50 rounded-full mb-6">
            <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
            <span class="text-[16] font-medium font-[Poppins] text-[#19191A]  tracking-wide">Glass to Plastic
              Conversion</span>
          </div>

          <h2 class="reveal text-[40px] font-medium font-[Helvetica] text-[#19191A] mb-2">
            Glass to Plastic Conversion
          </h2>

          <p class="reveal text-[#767C8C] text-[16px] font-medium font-[Montserrat] leading-relaxed mb-8">
            Many manufacturers are switching from glass components to plastic alternatives to improve durability, reduce
            weight, and lower production costs. Our <span class="font-bold text-[#474C59]">Glass to Plastic
              Conversion</span> service helps businesses redesign
            existing glass products into high-performance plastic components using advanced injection moulding
            technology. <br> By replacing glass with engineered plastics, products can maintain their functionality
            while
            gaining improved strength, safety, and design flexibility.
          </p>
        </div>
      </div>
    </div>
  </section>
  <!-- ========= Glass to Plastic Conversion end ======================= -->

  <!-- ================= Process start================= -->
  <section class="relative py-20 overflow-hidden bg-[#00191C]">
    <div class="reveal text-center  px-6">
      <p class="text-[#C8FF00] font-medium text-[20px] tracking-[0.3em] font-[Helvetica] mb-4">Benefits</p>
      <h2 class="font-medium text-[40px] font-[Helvetica] text-[#FFFFFF] mb-2">
       Why Convert Glass to<span class="text-[#C8FF00] "> Plastic? </span>
      </h2>
      <p class="text-[#FFFFFF] max-w-xl mx-auto text-[#18px] font-[Montserrat] font-regular">
       Switching from glass to plastic offers significant advantages across weight, durability, design, and cost.
      </p>
    </div>
    <div class="reveal max-w-7xl mx-auto relative px-6 py-16">
      <div class="reveal grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">

  <!-- CARD -->
        <div class=" ">
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
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Reduced Weight.png" alt="Injection" class=" shadow-md" />
              </div>
              <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 1</p>
            </div>

            <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
             Reduced Weight
            </h3>
            <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
              Plastic components are significantly lighter than glass, making products easier to transport, handle, and install while reducing shipping and logistics costs.
            </p>
          </div>
        </div>

        <!-- CARD -->
        <div class=" ">
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
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Improved Durability.png" alt="Injection" class=" shadow-md" />
              </div>
              <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 2</p>
            </div>

            <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
             Improved Durability
            </h3>
            <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
             Unlike glass, plastic materials are highly resistant to impact and breakage. This improves product safety and increases the lifespan of the component.
            </p>
          </div>
        </div>
        <!-- CARD -->

        <div class=" ">
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
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Design Flexibility.png" alt="Injection" class=" shadow-md" />
              </div>
              <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 3</p>
            </div>

            <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
              Design Flexibility
            </h3>
            <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
              Injection moulding allows for more complex shapes and integrated features that may be difficult or impossible to achieve with glass manufacturing.
            </p>
          </div>
        </div>
        <!-- CARD -->

        <div class="">
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
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Cost Efficiency.png" alt="Cost Efficiency" class=" shadow-md" />
              </div>
              <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 4</p>
            </div>

            <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
             Cost Efficiency
            </h3>
            <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
             Plastic components can reduce overall production costs, especially when producing medium to high volumes, while maintaining consistent quality.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="reveal text-center mb-32 px-6">
      <p class="text-[#C8FF00] font-medium text-[20px] tracking-[0.3em] font-[Helvetica] mb-4">Process</p>
      <h2 class="font-medium text-[40px] font-[Helvetica] text-[#FFFFFF] mb-2">
        Our Conversion <span class="text-[#C8FF00] ">Process</span>
      </h2>
      <p class="text-[#FFFFFF] max-w-7xl mx-auto text-[#18px] font-[Montserrat] font-regular">
        A structured approach to converting your glass components into high-performance plastic parts.
      </p>
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

              <div
                class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Frame 2147230828.png" alt="Mould Clamp" class=" shadow-md" />
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Product Analysis
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                Our team evaluates the original glass component and identifies suitable plastic materials that meet the
                required strength, transparency, or chemical resistance.
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

              <div
                class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/design optimization.png" alt="Injection" class=" shadow-md" />
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Design Optimization
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                We adapt the product design for injection moulding to ensure efficient production while maintaining the
                functionality of the original glass component.
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

              <div
                class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/material selection.png" alt="Cooling / Solidifying" class=" shadow-md" />
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Material Selection
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                We select the most suitable engineering plastics such as polycarbonate, polypropylene, or other
                specialised materials depending on the application.
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

              <div
                class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Production & Quality Control.png" alt="Ejection Finished Part"
                  class=" shadow-md" />
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Production & Quality Control
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                Once the design and material are finalised, the component is manufactured using precision injection
                moulding with strict quality control procedures.
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
  <!-- ================= Moulding Process end ================= -->

  <section class="py-24 bg-[#F5F6FA] font-sans">
    <div class="reveal max-w-7xl mx-auto px-6">

      <div class="reveal text-center mb-16">
        <div class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#D7FF80]/50 mb-4">
          <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
          <span class="reveal text-[16px] font-medium text-[#19191A] font-[Poppins] tracking-widest">Applications</span>
        </div>
        <h3 class="reveal text-[40px] font-medium text-[#19191A] font-[Helvetica] tracking-widest">Industries That
          Benefit
        </h3>
        <p class="reveal text-[16px] font-medium text-[#767C8C] font-[Montserrat] leading-relaxed mb-4">
          Glass-to-plastic conversion is commonly used in:
        </p>
      </div>
      <div class="reveal bg-white border border-slate-100 rounded-2xl p-8 text-center mb-12 shadow-sm">
        <h3 class="reveal text-[24px] font-bold font-[Montserrat] text-[#0E0E0E] mb-4">Consistent Material Performance
        </h3>
        <p class="text-[18px] font-medium font-[Montserrat] text-[#767C8C] max-w-5xl mx-auto leading-relaxed">
          By combining high-quality base polymers with carefully selected masterbatches, we are able to produce plastic
          components with <span class="text-[18px] font-semibold font-[Montserrat] text-[#474C59]">consistent colour,
            improved durability, and enhanced performance.</span>
        </p>
      </div>
      <div class="reveal grid grid-cols-1 md:grid-cols-2 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3">
            <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Consumer products</span>
          </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3">
            <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Industrial equipment</span>
          </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3">
            <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Automotive components</span>
          </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3">
            <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Packaging solutions</span>
          </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3">
            <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Laboratory equipment</span>
          </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <div class="flex items-center gap-3">
            <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Medical equipment</span>
          </div>
        </div>

      </div>

  </section>

  <!-- ================= faq section start ================= -->
  <section class="w-full bg-[#142F32] py-16 px-6 md:px-12 lg:px-24 font-[Poppins]">

    <div class="reveal max-w-4xl mx-auto text-center pb-4">
      <p class="reveal text-[#C8FF00] font-medium font-[Helvetica] text-[20px] tracking-widest  uppercase">FAQ
      </p>
      <h2 class="text-white text-[24px] md:text-[40px] font-medium font-[Helvetica] text-[20px] ">
        Frequently Asked <span class="text-[#C8FF00]">Questions</span>
      </h2>
    </div>

    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              01
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What types of glass products can be converted to plastic?</h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            Many housings, covers, containers, lenses, and display parts can be redesigned in plastic.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              02
            </span>

            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              Will the plastic replacement be as strong as glass?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            In many cases, engineered plastics can offer excellent strength with better impact resistance and lower weight.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              03
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              How long does the conversion process take?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            Timing depends on design and tooling, but most projects begin with a review and quotation within days.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              04
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              Can plastic achieve the same transparency as glass?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            Yes, clear materials such as polycarbonate and acrylic can provide high transparency for many applications.
          </p>
        </div>
      </div>

    </div>


  </section>

  <!-- ================= FAQ section end ================= -->
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
        class="text-[32px] md:text-[40px] lg:text-[40px] font-bold font-[Poppins] tracking-wide leading-tight  mt-3">
        Looking to replace glass<br>
        <span class="text-[#C8FF00]">components with plastic?</span>
      </h2>

      <p class="text-[16px] md:text-[16px] text-slate-200 font-[Poppins] font-medium max-w-xl mb-6 mt-3">
        Our team can help redesign and manufacture durable plastic alternatives tailored to your product requirements.
      </p>

      <a href="/contact-us"
        class="inline-flex items-center gap-3 rounded-full border border-white px-6 py-2 text-[12px] md:text-[18px] lg:text-[18px] font-medium font-[Montserrat] text-white transition-all  active:scale-95">
        Request a Quote
        <span class="text-xl leading-none  transition-transform"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png" alt=""
            class="w-[20px] h-[20px]"></span>
      </a>

    </div>
  </section>
  <!-- ================= Join Us Now section end ================= -->

</main>
<?php get_footer(); ?>



