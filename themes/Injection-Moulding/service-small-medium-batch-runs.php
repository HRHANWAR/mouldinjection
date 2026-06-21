<?php
/**
 * Template Name: Service - Small & Medium Batch Runs
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

  <section class="reveal py-20 bg-[#F5F6FA]">
    <div class="max-w-7xl mx-auto px-6">
      <div class="flex flex-col lg:flex-row items-center gap-16">

        <div class="revealw-full lg:w-1/2">
          <div class="reveal inline-flex items-center gap-2 px-3 py-1 bg-[#D7FF80]/50 rounded-full mb-6">
            <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
            <span class="text-[16] font-medium font-[Poppins] text-[#19191A]  tracking-wide">Flexible
              Manufacturing</span>
          </div>

          <h2 class="reveal text-[40px] font-medium font-[Helvetica] text-[#19191A] mb-2">
            Small & Medium Batch Runs
          </h2>

          <p class="reveal text-[#767C8C] text-[16px] font-medium font-[Montserrat] leading-relaxed mb-8">
            Not every product requires large-scale production. Our small and medium batch run service is designed for
            businesses that need reliable manufacturing in controlled quantities without committing to high-volume
            production.
          </p>

          <div class="grid grid-cols-2 gap-y-6 relative">
            

            <div class="pr-8 border-r-2">
              <h3 class="reveal text-[32px] font-medium font-[Helvetica] text-[#15181F] mb-1 tracking-wide">500+</h3>
              <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59]">Min. Order Quantity</p>
            </div>

            <div class="pl-8">
              <h3 class="reveal text-[32px] font-medium font-[Helvetica] text-[#15181F] mb-1 tracking-wide">50K</h3>
              <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59] ">Max Medium Batch</p>
            </div>

            <div class="col-span-2 h-[1px] bg-slate-200 my-2"></div>

            <div class="pr-8  border-r-2">
              <h3 class="reveal text-[32px] font-medium font-[Helvetica] text-[#15181F] mb-1 tracking-wide">Fast</h3>
              <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59]">Turnaround Times</p>
            </div>

            <div class="pl-8">
              <h3 class="reveal text-[32px] font-medium font-[Helvetica] text-[#15181F] mb-1 tracking-wide">100%</h3>
              <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59]">Quality Assured</p>
            </div>
          </div>
        </div>

        <div class="w-full lg:w-1/2">
          <div class=" rounded-[32px] w-full aspect-[4/3] shadow-lg">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/SmallMediumBatchRuns/Flexible Manufacturing.png" alt="">
          </div>
        </div>

      </div>
    </div>
  </section>
  <!-- ================= about service start ================= -->
  <section class="bg-[#142F32] text-white py-16 px-4 sm:px-6 lg:px-12">
    <div class="reveal max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-10 items-center">
      <!-- LEFT CONTENT -->
      <div>
        <!-- Tag box-->
        <div class="reveal flex">
          <div class="relative group">
            <div
              class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]">
            </div>

            <div
              class="flex items-center gap-3 px-6 py-2 bg-[#051616]/50 border border-white/10 rounded-2xl backdrop-blur-sm">
              <span class="w-2 h-2 bg-white rounded-full"></span>

              <span class="text-white text-[12px] lg:text-[18px] font-[Poppins] font-light tracking-wide">
                About The Service
              </span>
            </div>
          </div>
        </div>

        <!-- Heading -->
        <h2
          class="reveal font-[Helvetica] text-[24px] lg:text-[40px] text-[#FFFFFF] font-medium tracking-5% py-2 leading-tight tracking-widest">
          Flexible
          <span class="text-[#C8FF00]">Production Volumes</span>
        </h2>

        <!-- Description -->
        <p
          class="reveal mt-2 sm:text-base max-w-2xl text-[#FFFFFF] font-regular text-[18px] font-[Montserrat] leading-relaxed py-2">
          Our manufacturing capabilities allow us to produce small to medium production runs efficiently, giving
          businesses the flexibility to adjust production based on demand. This helps reduce excess inventory and lowers
          overall risk.We support companies at various stages of product development, from early market testing to
          regular small-scale production, while maintaining the same high standards of precision and quality.
        </p>

        <h4
          class="reveal font-[Helvetica] text-[20px] lg:text-[24px] text-[#FFFFFF] font-medium tracking-5% py-2 leading-tight tracking-widest">
          Ideal for Growing Businesses</h4>
        <div class="grid grid-cols-1 gap-4 pt-4">
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">New product launches</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">Advanced thermoplastic & thermoset
              processing</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">Multi-cavity tooling for high
              efficiency</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">Surface finish from SPI A-1 to
              D-3</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">Material traceability & quality
              certificates</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">ISO 9001 certified production
              partners</span>
          </div>


        </div>
      </div>


      <div class="max-w-4xl mx-auto px-6">

        <div class="relative group mb-8">
          <div
            class="absolute -inset-1 bg-[#062f2f]  bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] to-transparent rounded-[24px] blur opacity-50 group-hover:opacity-100 transition duration-1000">
          </div>

          <div
            class="relative bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] backdrop-blur-xl border border-white/5 p-6 rounded-[24px]">
            <div class="flex items-center gap-4 mb-4">
              <div
                class="w-10 h-10 bg-[#C8FF00]/10 rounded-lg border border-[#C8FF00]/30 flex items-center justify-center shadow-[0_0_15px_rgba(200,255,0,0.2)]">
                <img src="https://img.icons8.com/ios-filled/50/C8FF00/settings.png" class="w-5 h-5"
                  alt="Production Icon">
              </div>
              <h3 class="text-[22px] font-semibold font-[Poppins] tracking-wide">Production Volumes</h3>
            </div>

            <div class="grid grid-cols-2 bg-white/5 rounded-lg p-2 ">
              <span class="text-[20px] font-medium font-[Montserrat] text-white tracking-wider">Production Type</span>
              <span class="text-[20px] font-medium font-[Montserrat] text-white tracking-wider">Volume Range</span>
            </div>

            <div class="space-y-1">
              <div class="grid grid-cols-2 p-3 border-b border-white/5">
                <span class="text-[18px] font-medium font-[Montserrat] text-white tracking-wider">Small Batch</span>
                <span class="text-[16px] font-medium font-[Montserrat] text-[#C8CCD9] tracking-wider">500 – 5000
                  units</span>
              </div>
              <div class="grid grid-cols-2 p-3 border-b border-white/5">
                <span class="text-[18px] font-medium font-[Montserrat] text-white tracking-wider">Medium Batch</span>
                <span class="text-[16px] font-medium font-[Montserrat] text-[#C8CCD9] tracking-wider">5000 – 15000
                  units</span>
              </div>
              <div class="grid grid-cols-2 p-3">
                <span class="text-[18px] font-medium font-[Montserrat] text-white tracking-wider">Scalable
                  Production</span>
                <span class="text-[16px] font-medium font-[Montserrat] text-[#C8CCD9] tracking-wider">Higher volumes
                  available</span>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

          <div class="relative group">
            <!-- Neon Line -->
            <div
              class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
            </div>
            <div
              class="relative bg-[#062f2f] rounded-[28px]  bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] backdrop-blur-xl border border-white/5 p-6 rounded-[24px] ">
              <div
                class="w-10 h-10 bg-[#C8FF00]/10 rounded-lg border border-[#C8FF00]/30 flex items-center justify-center mb-6 shadow-[0_0_15px_rgba(200,255,0,0.2)]">
                <img src="https://img.icons8.com/ios-filled/50/C8FF00/guarantee.png" class="w-5 h-5" alt="Quality Icon">
              </div>
              <h4 class="text-[18px] font-semibold font-[Poppins] text-[#EBEDF0] tracking-wider mb-3">Consistent Quality
              </h4>
              <p class="text-[16px] font-medium font-[Montserrat] text-[#FFFFFF]/60 tracking-wider">
                Strict quality standards ensure consistent results across all units.
              </p>
            </div>
          </div>

          <div class="relative group">
            <!-- Neon Line -->
            <div
              class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
            </div>
            <div
              class="relative bg-[#062f2f] rounded-[28px]  bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] backdrop-blur-xl border border-white/5 p-6 rounded-[24px] h-full">
              <div
                class="w-10 h-10 bg-[#C8FF00]/10 rounded-lg border border-[#C8FF00]/30 flex items-center justify-center mb-6 shadow-[0_0_15px_rgba(200,255,0,0.2)]">
                <img src="https://img.icons8.com/ios-filled/50/C8FF00/time.png" class="w-5 h-5" alt="Time Icon">
              </div>
              <h4 class="text-[18px] font-semibold font-[Poppins] text-[#EBEDF0] tracking-wider mb-3">Efficient
                Turnaround</h4>
              <p class="text-[16px] font-medium font-[Montserrat] text-[#FFFFFF]/60 tracking-wider">
                Streamlined process delivers orders quickly and efficiently.
              </p>
            </div>
          </div>

        </div>
      </div>


    </div>
  </section>
  <!-- ================= about service end ================= -->
  <!-- ================= why chose us start ================= -->
  <section class="reveal bg-[#F5F6FA] py-16 sm:py-20 lg:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <!-- TOP TEXT -->
      <p
        class="inline-flex items-center gap-2 rounded-full bg-[#d8efad] px-5 py-1 sm:text-[13px] text-[16px] font-[Poppins] font-medium text-[#19191A] tracking-wide">
        <span class="h-2 w-2 rounded-full bg-[#19191A]"></span>
        Advantages
      </p>

      <!-- HEADING -->
      <h2
        class="reveal text-[25px] sm:text-2xl md:text-[40px] tracking-wide font-bold mt-2 font-[Helvetica] text-[#19191A] leading-tight mx-auto pt-3 tracking-wide">
        Why Small Batch Manufacturing Works
      </h2>

      <!-- DESCRIPTION -->
      <p
        class="reveal text-[#767C8C] font-medium font-[Montserrat] mt-4 sm:mt-6 text-[16px] max-w-7xl mx-auto leading-relaxed">
        Reduce risk, increase flexibility, and get to market faster with controlled production volumes.
      </p>

      <!-- CARDS -->
      <div class="reveal grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 sm:gap-6 mt-5 sm:mt-14">
        <!-- CARD 1 -->
        <div
          class="group relative bg-[#FFFFFF] p-8  sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                 mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/SmallMediumBatchRuns/icons/secure.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/SmallMediumBatchRuns/icons/secure.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
          <h3
            class="text-[24px] font-medium font-[Helvetica] tracking-wide text-[#15181F] mb-2 sm:mb-3 transition-colors duration-300 group-hover:text-white leading-tight">
            Lower Investment Risk
          </h3>

          <!-- TEXT -->
          <p
            class="text-[#474C59] text-[18px] font-[Montserrat] font-regular leading-relaxed transition-colors duration-300 group-hover:text-white leading-tight">
            Produce manageable quantities without committing to large production runs.
          </p>
        </div>
        <!-- CARD 2 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                  mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/SmallMediumBatchRuns//icons/clock.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/SmallMediumBatchRuns/icons/clock.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
          <h3
            class="text-[24px] font-medium font-[Helvetica] tracking-wide text-[#15181F] mb-2 sm:mb-3 transition-colors duration-300 group-hover:text-white leading-tight">
            Faster Production Scheduling
          </h3>

          <!-- TEXT -->
          <p
            class="text-[#474C59] text-[18px] font-[Montserrat] font-regular leading-relaxed transition-colors duration-300 group-hover:text-white leading-tight">
            Smaller batches allow quicker machine scheduling and faster delivery.
          </p>
        </div>
        <!-- CARD 3 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                 mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/SmallMediumBatchRuns/icons/Flexible\ Scaling.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/SmallMediumBatchRuns/icons/Flexible\ Scaling.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
          <h3
            class="text-[24px] font-medium font-[Helvetica] tracking-wide text-[#15181F] mb-2 sm:mb-3 transition-colors duration-300 group-hover:text-white leading-tight">
            Flexible Scaling
          </h3>

          <!-- TEXT -->
          <p
            class="text-[#474C59] text-[18px] font-[Montserrat] font-regular leading-relaxed transition-colors duration-300 group-hover:text-white leading-tight">
            Increase production as product demand grows.<br><br>
          </p>
        </div>

        <!-- CARD 4 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                  mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/SmallMediumBatchRuns/icons/Frame.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/SmallMediumBatchRuns/icons/Frame.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
          <h3
            class="text-[24px] font-medium font-[Helvetica] tracking-wide text-[#15181F] mb-2 sm:mb-3 transition-colors duration-300 group-hover:text-white leading-tight">
            Perfect for Product Launches
          </h3>

          <!-- TEXT -->
          <p
            class="text-[#474C59] text-[18px] font-[Montserrat] font-regular leading-relaxed transition-colors duration-300 group-hover:text-white leading-tight">
            Ideal for startups or businesses testing the market.<br><br>
          </p>
        </div>

      </div>
    </div>
  </section>
  <!-- ================= why chose us end ================= -->
  <!-- ================= faq section start ================= -->
  <section class="w-full bg-[#142F32] py-16 px-6 md:px-12 lg:px-24 font-[Poppins]">

    <div class="reveal max-w-4xl mx-auto text-center pb-4">
      <p class="reveal text-[#C8FF00] font-medium font-[Helvetica] text-[20px] tracking-widest   uppercase">FAQ
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
              What counts as a small or medium batch run?</h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            Small and medium batch runs usually range from a few hundred to several thousand parts, depending on the project.
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
              Is the quality the same as large-scale production?
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
            Yes, the same machines, moulds, and quality controls are used to maintain consistent standards.
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
              How quickly can you deliver small batch orders?
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
            Lead times vary, but many small batch orders can be produced faster due to shorter scheduling times.
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
              Can I scale up later if demand increases?
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
            Yes, production volumes can be increased as demand grows and machine capacity becomes available.
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
              05
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What materials are available for batch production?
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
            We support common plastics such as PP, ABS, Nylon, PC, HDPE, and many engineering materials.
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
        Ready to Start Your
        <span class="text-[#C8FF00]">Batch Run?</span>
      </h2>

      <p class="text-[16px] md:text-[16px] text-slate-200 font-[Poppins] font-medium max-w-2xl mb-6 mt-3">
        Get a free quote for your small or medium batch production. Our team will match you with the best manufacturer
        for your requirements.
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



